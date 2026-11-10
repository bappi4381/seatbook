<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ((int) $user->id !== (int) $booking->user_id) {
            return false;
        }

        if ($booking->status !== 'confirmed') {
            return false;
        }

        $booking->loadMissing('event');

        return $booking->event->starts_at->isFuture();
    }
}
