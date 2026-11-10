<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Seat;

class SeatGeneratorService
{
    /**
     * Generate seats for an event in a single bulk insert.
     * Rows are labeled A..Z for the requested row count.
     */
    public function generate(Event $event, int $rows, int $seatsPerRow, float|int|string $price): void
    {
        $seats = [];
        $now = now();

        for ($i = 0; $i < $rows; $i++) {
            $row = chr(ord('A') + $i);

            for ($number = 1; $number <= $seatsPerRow; $number++) {
                $seats[] = [
                    'event_id' => $event->id,
                    'label' => $row.$number,
                    'row' => $row,
                    'number' => $number,
                    'price' => $price,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Seat::insert($seats);
    }
}
