<?php

namespace App\Support;

use App\Models\Seat;
use App\Models\User;

class SeatState
{
    public static function for(Seat $seat, ?User $user = null): string
    {
        if ($seat->status === 'booked') {
            return 'booked';
        }

        $hold = $seat->relationLoaded('hold') ? $seat->hold : null;

        if ($hold && $hold->expires_at->isFuture()) {
            if ($user && (int) $hold->user_id === (int) $user->id) {
                return 'held_by_me';
            }

            return 'held_by_others';
        }

        return 'available';
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(Seat $seat, ?User $user = null): array
    {
        $state = self::for($seat, $user);
        $hold = $seat->relationLoaded('hold') ? $seat->hold : null;

        return [
            'id' => $seat->id,
            'label' => $seat->label,
            'row' => $seat->row,
            'number' => $seat->number,
            'price' => (float) $seat->price,
            'state' => $state,
            'expires_at' => $state === 'held_by_me' && $hold
                ? $hold->expires_at->toIso8601String()
                : null,
        ];
    }
}
