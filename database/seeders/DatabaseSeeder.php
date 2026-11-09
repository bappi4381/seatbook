<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Booking;
use App\Models\SeatHold;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organizer = User::create([
            'name' => 'Organizer', 'email' => 'organizer@example.com',
            'password' => bcrypt('password'), 'role' => 'organizer',
        ]);

        foreach ([1, 2] as $i) {
            User::create([
                'name' => "Attendee $i", 'email' => "attendee$i@example.com",
                'password' => bcrypt('password'), 'role' => 'attendee',
            ]);
        }

        foreach (['Rock Night', 'Tech Summit', 'Stand-up Comedy'] as $i => $title) {
            $event = Event::create([
                'organizer_id' => $organizer->id,
                'title' => $title,
                'description' => "$title description",
                'venue' => 'Dhaka Hall',
                'starts_at' => now()->addDays($i + 7),
            ]);

            foreach (range('A', 'E') as $row) {
                foreach (range(1, 10) as $n) {
                    Seat::create([
                        'event_id' => $event->id,
                        'label' => $row.$n,
                        'row' => $row,
                        'number' => $n,
                        'price' => 500,
                    ]);
                }
            }
        }
    }
}
