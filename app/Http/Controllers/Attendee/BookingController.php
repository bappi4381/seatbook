<?php

namespace App\Http\Controllers\Attendee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendee\CancelBookingRequest;
use App\Http\Requests\Attendee\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Event;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::query()
            ->where('user_id', auth()->id())
            ->with(['event', 'seats'])
            ->latest()
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function store(StoreBookingRequest $request, Event $event, BookingService $bookingService): RedirectResponse
    {
        try {
            $booking = $bookingService->confirm($event, $request->user());
        } catch (HttpException $exception) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking confirmed successfully.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['event', 'seats']);

        return view('bookings.show', compact('booking'));
    }

    public function cancel(CancelBookingRequest $request, Booking $booking, BookingService $bookingService): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        try {
            $bookingService->cancel($booking);
        } catch (HttpException $exception) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.index')
            ->with('status', 'Booking cancelled successfully.');
    }
}
