<?php

namespace Tests\Feature\Organizer;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_event_with_generated_seats(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer)->post(route('organizer.events.store'), [
            'title' => 'Rock Night',
            'description' => 'Live music',
            'venue' => 'Dhaka Hall',
            'starts_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'rows' => 3,
            'seats_per_row' => 4,
            'price' => 750,
        ]);

        $response->assertRedirect(route('organizer.events.index'));

        $event = Event::where('title', 'Rock Night')->first();

        $this->assertNotNull($event);
        $this->assertSame($organizer->id, $event->organizer_id);
        $this->assertSame(12, $event->seats()->count());
        $this->assertDatabaseHas('seats', [
            'event_id' => $event->id,
            'label' => 'A1',
            'price' => 750,
        ]);
        $this->assertDatabaseHas('seats', [
            'event_id' => $event->id,
            'label' => 'C4',
        ]);
    }

    public function test_store_validation_errors_are_returned(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer)->post(route('organizer.events.store'), [
            'title' => '',
            'venue' => '',
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'rows' => 30,
            'seats_per_row' => 50,
            'price' => -10,
        ]);

        $response->assertSessionHasErrors(['title', 'venue', 'starts_at', 'rows', 'seats_per_row', 'price']);
        $this->assertSame(0, Event::count());
    }

    public function test_another_organizer_cannot_edit_or_delete_event(): void
    {
        $owner = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        $event = Event::factory()->for($owner, 'organizer')->create();

        $this->actingAs($other)
            ->get(route('organizer.events.edit', $event))
            ->assertForbidden();

        $this->actingAs($other)
            ->put(route('organizer.events.update', $event), [
                'title' => 'Hacked',
                'description' => null,
                'venue' => 'Elsewhere',
                'starts_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
                'status' => 'draft',
                'price' => 100,
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('organizer.events.destroy', $event))
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => $event->title,
        ]);
    }

    public function test_attendee_cannot_access_organizer_event_routes(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();

        $this->actingAs($attendee)
            ->get(route('organizer.events.index'))
            ->assertForbidden();

        $this->actingAs($attendee)
            ->get(route('organizer.events.create'))
            ->assertForbidden();

        $this->actingAs($attendee)
            ->post(route('organizer.events.store'), [
                'title' => 'Nope',
                'venue' => 'Hall',
                'starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'rows' => 2,
                'seats_per_row' => 2,
                'price' => 100,
            ])
            ->assertForbidden();

        $this->actingAs($attendee)
            ->get(route('organizer.events.edit', $event))
            ->assertForbidden();

        $this->actingAs($attendee)
            ->delete(route('organizer.events.destroy', $event))
            ->assertForbidden();
    }

    public function test_delete_is_blocked_when_confirmed_bookings_exist(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->for($organizer, 'organizer')->create();
        $seat = Seat::factory()->for($event)->create(['label' => 'A1', 'row' => 'A', 'number' => 1]);

        $booking = Booking::factory()->create([
            'event_id' => $event->id,
            'status' => 'confirmed',
            'total_amount' => $seat->price,
        ]);
        $booking->seats()->attach($seat->id);

        $response = $this->actingAs($organizer)
            ->from(route('organizer.events.index'))
            ->delete(route('organizer.events.destroy', $event));

        $response
            ->assertRedirect(route('organizer.events.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_organizer_can_update_event_and_unbooked_seat_prices(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->for($organizer, 'organizer')->create();
        $freeSeat = Seat::factory()->for($event)->create([
            'label' => 'A1',
            'row' => 'A',
            'number' => 1,
            'price' => 100,
        ]);
        $bookedSeat = Seat::factory()->for($event)->booked()->create([
            'label' => 'A2',
            'row' => 'A',
            'number' => 2,
            'price' => 100,
        ]);
        $booking = Booking::factory()->create([
            'event_id' => $event->id,
            'status' => 'confirmed',
            'total_amount' => 100,
        ]);
        $booking->seats()->attach($bookedSeat->id);

        $response = $this->actingAs($organizer)->put(route('organizer.events.update', $event), [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'venue' => 'New Venue',
            'starts_at' => now()->addDays(14)->format('Y-m-d H:i:s'),
            'status' => 'draft',
            'price' => 250,
        ]);

        $response->assertRedirect(route('organizer.events.index'));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
            'status' => 'draft',
        ]);
        $this->assertSame('250.00', $freeSeat->fresh()->price);
        $this->assertSame('100.00', $bookedSeat->fresh()->price);
    }

    public function test_organizer_dashboard_redirects_to_events_index(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get('/organizer/dashboard')
            ->assertRedirect('/organizer/events');
    }
}
