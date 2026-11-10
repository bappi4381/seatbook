<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SeatHoldService
{
    public const HOLD_MINUTES = 10;

    public const MAX_SEATS_PER_EVENT = 6;

    public const CONFLICT_MESSAGE = 'One or more seats are no longer available. Please choose different seats.';

    /**
     * @param  list<int>  $seatIds
     * @return Collection<int, SeatHold>
     */
    public function hold(Event $event, User $user, array $seatIds): Collection
    {
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));

        try {
            return DB::transaction(function () use ($event, $user, $seatIds) {
                if ($event->starts_at->isPast()) {
                    throw new HttpException(409, 'This event has already started.');
                }

                $seats = Seat::query()
                    ->where('event_id', $event->id)
                    ->whereIn('id', $seatIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($seats->count() !== count($seatIds)) {
                    throw new HttpException(409, self::CONFLICT_MESSAGE);
                }

                SeatHold::query()
                    ->whereIn('seat_id', $seatIds)
                    ->where('expires_at', '<=', now())
                    ->delete();

                Seat::query()
                    ->whereIn('id', $seatIds)
                    ->where('status', 'held')
                    ->whereDoesntHave('hold', fn ($query) => $query->where('expires_at', '>', now()))
                    ->update(['status' => 'available']);

                $activeHolds = SeatHold::query()
                    ->whereIn('seat_id', $seatIds)
                    ->where('expires_at', '>', now())
                    ->get()
                    ->keyBy('seat_id');

                foreach ($seats as $seat) {
                    if ($seat->status === 'booked') {
                        throw new HttpException(409, self::CONFLICT_MESSAGE);
                    }

                    $hold = $activeHolds->get($seat->id);
                    if ($hold && $hold->user_id !== $user->id) {
                        throw new HttpException(409, self::CONFLICT_MESSAGE);
                    }
                }

                // Re-holding own seats must not duplicate or extend the hold
                // (decision: no extension — existing expires_at stays as-is).
                $alreadyHeldIds = $activeHolds
                    ->filter(fn (SeatHold $hold) => $hold->user_id === $user->id)
                    ->keys()
                    ->all();

                $newSeatIds = array_values(array_diff($seatIds, $alreadyHeldIds));

                $existingActiveCount = SeatHold::query()
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->whereHas('seat', fn ($query) => $query->where('event_id', $event->id))
                    ->count();

                if ($existingActiveCount + count($newSeatIds) > self::MAX_SEATS_PER_EVENT) {
                    throw new HttpException(409, 'You can hold at most '.self::MAX_SEATS_PER_EVENT.' seats for this event.');
                }

                $expiresAt = now()->addMinutes(self::HOLD_MINUTES);

                foreach ($newSeatIds as $seatId) {
                    SeatHold::create([
                        'seat_id' => $seatId,
                        'user_id' => $user->id,
                        'expires_at' => $expiresAt,
                    ]);

                    Seat::whereKey($seatId)->update(['status' => 'held']);
                }

                return SeatHold::query()
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->whereHas('seat', fn ($query) => $query->where('event_id', $event->id))
                    ->with('seat')
                    ->get();
            });
        } catch (UniqueConstraintViolationException) {
            throw new HttpException(409, self::CONFLICT_MESSAGE);
        }
    }

    public function releaseForUser(Event $event, User $user): int
    {
        return DB::transaction(function () use ($event, $user) {
            $holds = SeatHold::query()
                ->where('user_id', $user->id)
                ->whereHas('seat', fn ($query) => $query->where('event_id', $event->id))
                ->lockForUpdate()
                ->get();

            $seatIds = $holds->pluck('seat_id');

            SeatHold::query()->whereIn('id', $holds->pluck('id'))->delete();

            if ($seatIds->isNotEmpty()) {
                Seat::query()
                    ->whereIn('id', $seatIds)
                    ->where('status', 'held')
                    ->update(['status' => 'available']);
            }

            return $holds->count();
        });
    }

    public function releaseExpired(): int
    {
        return DB::transaction(function () {
            $holds = SeatHold::query()
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            if ($holds->isEmpty()) {
                return 0;
            }

            $seatIds = $holds->pluck('seat_id');

            SeatHold::query()->whereIn('id', $holds->pluck('id'))->delete();

            Seat::query()
                ->whereIn('id', $seatIds)
                ->where('status', 'held')
                ->update(['status' => 'available']);

            return $holds->count();
        });
    }
}
