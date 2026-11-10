<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\User;
use App\Support\DatabaseContention;
use App\Support\EventSeatCache;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingService
{
    public const CONFIRM_CONFLICT_MESSAGE = 'One or more seats were just booked by someone else. Please choose different seats.';

    public function confirm(Event $event, User $user): Booking
    {
        try {
            $booking = DB::transaction(function () use ($event, $user) {
                if ($event->starts_at->isPast()) {
                    throw new HttpException(409, 'This event has already started.');
                }

                $holds = SeatHold::query()
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->whereHas('seat', fn ($query) => $query->where('event_id', $event->id))
                    ->with('seat')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($holds->isEmpty()) {
                    throw new HttpException(409, 'Your hold has expired or no seats are held. Please select seats again.');
                }

                $seats = Seat::query()
                    ->whereIn('id', $holds->pluck('seat_id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($seats->count() !== $holds->count() || $seats->contains(fn (Seat $seat) => $seat->status === 'booked')) {
                    throw new HttpException(409, SeatHoldService::CONFLICT_MESSAGE);
                }

                $total = $seats->sum(fn (Seat $seat) => (float) $seat->price);

                $booking = new Booking;
                $booking->user_id = $user->id;
                $booking->event_id = $event->id;
                $booking->status = 'confirmed';
                $booking->total_amount = $total;
                $booking->save();

                $booking->seats()->attach($seats->pluck('id')->all());

                Seat::query()
                    ->whereIn('id', $seats->pluck('id'))
                    ->update(['status' => 'booked']);

                SeatHold::query()->whereIn('id', $holds->pluck('id'))->delete();

                return $booking->load(['seats', 'event']);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw new HttpException(409, self::CONFIRM_CONFLICT_MESSAGE);
        } catch (QueryException $exception) {
            DatabaseContention::throwIfLockFailure($exception);
            throw $exception;
        }

        EventSeatCache::forget($event->id);

        return $booking;
    }

    public function cancel(Booking $booking): Booking
    {
        try {
            $booking = DB::transaction(function () use ($booking) {
                // Lock order: booking row first, then seats ordered by id (same as confirm) to avoid deadlocks.
                $booking = Booking::query()
                    ->whereKey($booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'confirmed') {
                    throw new HttpException(409, 'Only confirmed bookings can be cancelled.');
                }

                $booking->loadMissing('event', 'seats');

                if ($booking->event->starts_at->isPast()) {
                    throw new HttpException(409, 'This booking can no longer be cancelled because the event has started.');
                }

                $seatIds = $booking->seats->pluck('id');

                if ($seatIds->isNotEmpty()) {
                    Seat::query()
                        ->whereIn('id', $seatIds)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();
                }

                $booking->seats()->detach();
                $booking->status = 'cancelled';
                $booking->save();

                if ($seatIds->isNotEmpty()) {
                    $activeHoldSeatIds = SeatHold::query()
                        ->whereIn('seat_id', $seatIds)
                        ->where('expires_at', '>', now())
                        ->pluck('seat_id')
                        ->all();

                    Seat::query()
                        ->whereIn('id', $seatIds)
                        ->whereNotIn('id', $activeHoldSeatIds)
                        ->update(['status' => 'available']);

                    if ($activeHoldSeatIds !== []) {
                        Seat::query()
                            ->whereIn('id', $activeHoldSeatIds)
                            ->update(['status' => 'held']);
                    }
                }

                return $booking->fresh(['seats', 'event']);
            }, 3);
        } catch (QueryException $exception) {
            DatabaseContention::throwIfLockFailure($exception);
            throw $exception;
        }

        EventSeatCache::forget($booking->event_id);

        return $booking;
    }
}
