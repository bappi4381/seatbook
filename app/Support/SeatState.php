<?php

namespace App\Support;

use App\Models\Seat;
use App\Models\User;

class SeatState
{
    public static function for(Seat $seat, ?User $user = null): string
    {
        return self::personalize(self::sharedPayload($seat), $user)['state'];
    }

    /**
     * Shared seat snapshot (no per-user held_by_me / held_by_others).
     *
     * @return array<string, mixed>
     */
    public static function sharedPayload(Seat $seat): array
    {
        $hold = $seat->relationLoaded('hold') ? $seat->hold : null;
        $activeHold = $hold && $hold->expires_at->isFuture() ? $hold : null;

        $state = 'available';
        if ($seat->status === 'booked') {
            $state = 'booked';
        } elseif ($activeHold) {
            $state = 'held';
        }

        return [
            'id' => $seat->id,
            'label' => $seat->label,
            'row' => $seat->row,
            'number' => $seat->number,
            'price' => (float) $seat->price,
            'state' => $state,
            'hold_user_id' => $activeHold?->user_id,
            'expires_at' => $activeHold?->expires_at->toIso8601String(),
        ];
    }

    /**
     * Overlay the current viewer onto a shared seat snapshot.
     *
     * @param  array<string, mixed>  $shared
     * @return array<string, mixed>
     */
    public static function personalize(array $shared, ?User $user = null): array
    {
        $state = $shared['state'];
        $expiresAt = null;

        if ($state === 'held') {
            if ($user && (int) $shared['hold_user_id'] === (int) $user->id) {
                $state = 'held_by_me';
                $expiresAt = $shared['expires_at'];
            } else {
                $state = 'held_by_others';
            }
        }

        return [
            'id' => $shared['id'],
            'label' => $shared['label'],
            'row' => $shared['row'],
            'number' => $shared['number'],
            'price' => $shared['price'],
            'state' => $state,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(Seat $seat, ?User $user = null): array
    {
        return self::personalize(self::sharedPayload($seat), $user);
    }
}
