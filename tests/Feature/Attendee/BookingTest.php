<?php

namespace Tests\Feature\Attendee;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\User;
use App\Services\SeatHoldService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Real concurrent row-lock races need MySQL. These tests are sequential and prove
     * the second conflicting attempt fails under SQLite in-memory (fine for CI).
     */
    public function test_organizer_gets_forbidden_on_booking_routes(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create();
        $booking = Booking::factory()->create(['event_id' => $event->id]);

        $this->actingAs($organizer)
            ->post(route('events.bookings.store', $event))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->get(route('bookings.index'))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->delete(route('bookings.cancel', $booking))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_booking_write_routes(): void
    {
        $event = Event::factory()->create();
        $booking = Booking::factory()->create(['event_id' => $event->id]);

        $this->post(route('events.bookings.store', $event))
            ->assertRedirect(route('login'));

        $this->delete(route('bookings.cancel', $booking))
            ->assertRedirect(route('login'));
    }

    public function test_confirming_converts_holds_into_booking_with_server_calculated_total(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seatA = $this->makeSeat($event, 'A1', 1, 100);
        $seatB = $this->makeSeat($event, 'A2', 2, 250);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), [
                'seat_ids' => [$seatA->id, $seatB->id],
            ])
            ->assertCreated();

        $response = $this->actingAs($attendee)->post(route('events.bookings.store', $event), [
            // Client-sent totals must be ignored; amount is computed server-side.
            'total_amount' => 1,
            'status' => 'cancelled',
            'price' => 9999,
        ]);

        $booking = Booking::where('user_id', $attendee->id)->first();

        $this->assertNotNull($booking);
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('350.00', $booking->total_amount);
        $this->assertSame(2, $booking->seats()->count());
        $this->assertDatabaseCount('seat_holds', 0);
        $this->assertSame('booked', $seatA->fresh()->status);
        $this->assertSame('booked', $seatB->fresh()->status);
    }

    public function test_confirming_with_an_expired_hold_fails(): void
    {
        $this->freezeTime();

        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->travel(11)->minutes();

        $this->actingAs($attendee)
            ->from(route('events.show', $event))
            ->post(route('events.bookings.store', $event))
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertSame('held', $seat->fresh()->status);
    }

    public function test_double_booking_the_same_seat_is_impossible(): void
    {
        // Sequential conflict check (true concurrent lock races need MySQL).
        $first = User::factory()->attendee()->create();
        $second = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);

        $this->actingAs($first)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($first)
            ->post(route('events.bookings.store', $event))
            ->assertRedirect();

        $this->assertSame('booked', $seat->fresh()->status);

        // Second user cannot hold a booked seat.
        $this->actingAs($second)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertStatus(409)
            ->assertJsonFragment(['message' => SeatHoldService::CONFLICT_MESSAGE]);

        // DB-level unique on booking_seats.seat_id also prevents double booking.
        $otherBooking = Booking::factory()->create([
            'user_id' => $second->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'total_amount' => 100,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('booking_seats')->insert([
            'booking_id' => $otherBooking->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_cannot_confirm_another_users_hold(): void
    {
        $owner = User::factory()->attendee()->create();
        $other = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);

        $this->actingAs($owner)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($other)
            ->from(route('events.show', $event))
            ->post(route('events.bookings.store', $event))
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seat->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_owner_can_cancel_and_another_user_can_book_the_seats(): void
    {
        $owner = User::factory()->attendee()->create();
        $other = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 120);

        $this->actingAs($owner)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($owner)
            ->post(route('events.bookings.store', $event))
            ->assertRedirect();

        $booking = Booking::where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertDatabaseMissing('booking_seats', ['seat_id' => $seat->id]);
        $this->assertSame('available', $seat->fresh()->status);

        $this->actingAs($other)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($other)
            ->post(route('events.bookings.store', $event))
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'user_id' => $other->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'total_amount' => 120,
        ]);
        $this->assertSame('booked', $seat->fresh()->status);
    }

    public function test_other_users_cannot_cancel_booking(): void
    {
        $owner = User::factory()->attendee()->create();
        $other = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);

        $booking = $this->bookSeat($owner, $event, $seat);

        $this->actingAs($other)
            ->delete(route('bookings.cancel', $booking))
            ->assertForbidden();

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('booked', $seat->fresh()->status);
    }

    public function test_cancelling_twice_is_rejected(): void
    {
        $owner = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);
        $booking = $this->bookSeat($owner, $event, $seat);

        $this->actingAs($owner)
            ->delete(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->actingAs($owner)
            ->delete(route('bookings.cancel', $booking))
            ->assertForbidden();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_cancelling_after_event_started_is_rejected(): void
    {
        $this->freezeTime();

        $owner = User::factory()->attendee()->create();
        $event = Event::factory()->create([
            'starts_at' => now()->addHour(),
        ]);
        $seat = $this->makeSeat($event, 'A1', 1, 100);
        $booking = $this->bookSeat($owner, $event, $seat);

        $this->travel(2)->hours();

        $this->actingAs($owner)
            ->delete(route('bookings.cancel', $booking))
            ->assertForbidden();

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('booked', $seat->fresh()->status);
    }

    private function bookSeat(User $attendee, Event $event, Seat $seat): Booking
    {
        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($attendee)
            ->post(route('events.bookings.store', $event))
            ->assertRedirect();

        return Booking::where('user_id', $attendee->id)->latest('id')->firstOrFail();
    }

    private function makeSeat(Event $event, string $label, int $number, float $price = 100): Seat
    {
        return Seat::factory()->for($event)->create([
            'label' => $label,
            'row' => substr($label, 0, 1),
            'number' => $number,
            'price' => $price,
            'status' => 'available',
        ]);
    }
}
