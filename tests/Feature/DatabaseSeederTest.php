<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_organizer_with_organizer_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $organizer = User::where('email', 'organizer@example.com')->first();

        $this->assertNotNull($organizer);
        $this->assertSame('organizer', $organizer->role);
        $this->assertTrue($organizer->isOrganizer());
        $this->assertSame(2, User::where('role', 'attendee')->count());
        $this->assertSame(3, Event::count());
        $this->assertSame(150, Seat::count());
    }
}
