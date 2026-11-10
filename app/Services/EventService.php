<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EventService
{
    public function __construct(
        private readonly SeatGeneratorService $seatGenerator,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, venue: string, starts_at: mixed, rows: int|string, seats_per_row: int|string, price: mixed}  $data
     */
    public function create(User $organizer, array $data): Event
    {
        return DB::transaction(function () use ($organizer, $data) {
            $event = Event::create([
                'organizer_id' => $organizer->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'venue' => $data['venue'],
                'starts_at' => $data['starts_at'],
                'status' => 'published',
            ]);

            $this->seatGenerator->generate(
                $event,
                (int) $data['rows'],
                (int) $data['seats_per_row'],
                $data['price'],
            );

            return $event;
        });
    }

    /**
     * @param  array{title: string, description?: string|null, venue: string, starts_at: mixed, status: string, price: mixed}  $data
     */
    public function update(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $event->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'venue' => $data['venue'],
                'starts_at' => $data['starts_at'],
                'status' => $data['status'],
            ]);

            // Price applies only to seats with no active hold and no booking;
            // seats already booked/held keep their existing price.
            $event->seats()
                ->whereDoesntHave('hold', fn ($query) => $query->where('expires_at', '>', now()))
                ->whereDoesntHave('bookings')
                ->update(['price' => $data['price']]);

            return $event->refresh();
        });
    }

    public function delete(Event $event): void
    {
        if ($event->bookings()->where('status', 'confirmed')->exists()) {
            throw new HttpException(409, 'This event cannot be deleted because it has confirmed bookings.');
        }

        $event->delete();
    }
}
