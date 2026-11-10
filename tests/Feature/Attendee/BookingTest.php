<?php

namespace Tests\Feature\Attendee;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\User;
use App\Services\BookingService;
use App\Services\SeatHoldService;
use App\Support\DatabaseContention;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

/**
 * Concurrency-related cases here run sequentially on SQLite in-memory by default
 * (phpunit.xml). They prove the second conflicting attempt fails, not true parallel
 * InnoDB races. To run the suite against MySQL: create a `seatbook_test` database,
 * then `DB_CONNECTION=mysql DB_DATABASE=seatbook_test php artisan test`
 * (phpunit.xml env values are not force-locked, so shell env overrides work).
 */
class BookingTest extends TestCase
{
    use RefreshDatabase;
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

    public function test_confirm_unique_violation_returns_friendly_conflict_and_rolls_back(): void
    {
        $attendee = User::factory()->attendee()->create();
        $other = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);

        $conflicting = new Booking;
        $conflicting->user_id = $other->id;
        $conflicting->event_id = $event->id;
        $conflicting->status = 'confirmed';
        $conflicting->total_amount = 100;
        $conflicting->save();

        DB::table('booking_seats')->insert([
            'booking_id' => $conflicting->id,
            'seat_id' => $seat->id,
        ]);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($attendee)
            ->from(route('events.show', $event))
            ->post(route('events.bookings.store', $event))
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('error', BookingService::CONFIRM_CONFLICT_MESSAGE);

        $this->assertSame(1, Booking::count());
        $this->assertDatabaseMissing('bookings', ['user_id' => $attendee->id]);
        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seat->id,
            'user_id' => $attendee->id,
        ]);
    }

    public function test_cancel_leaves_active_hold_seat_as_held(): void
    {
        $owner = User::factory()->attendee()->create();
        $other = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);
        $booking = $this->bookSeat($owner, $event, $seat);

        // Inconsistent fixture: active hold exists while seat is still on a booking.
        SeatHold::create([
            'seat_id' => $seat->id,
            'user_id' => $other->id,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($owner)
            ->delete(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.index'));

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertDatabaseMissing('booking_seats', ['seat_id' => $seat->id]);
        $this->assertSame('held', $seat->fresh()->status);
        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seat->id,
            'user_id' => $other->id,
        ]);
    }

    public function test_confirm_request_cannot_mass_assign_status_or_total(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 175);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($attendee)->post(route('events.bookings.store', $event), [
            'status' => 'cancelled',
            'total_amount' => 1,
        ])->assertRedirect();

        $booking = Booking::where('user_id', $attendee->id)->firstOrFail();

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('175.00', $booking->total_amount);
    }

    public function test_confirm_maps_deadlock_to_friendly_response(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $pdo = new PDOException('Deadlock found when trying to get lock');
        $pdo->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock'];
        $exception = new QueryException('mysql', 'select * from seat_holds for update', [], $pdo);

        DB::partialMock()
            ->shouldReceive('transaction')
            ->once()
            ->andThrow($exception);

        $this->actingAs($attendee)
            ->from(route('events.show', $event))
            ->post(route('events.bookings.store', $event))
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('error', DatabaseContention::BUSY_MESSAGE);
    }

    public function test_cancel_maps_lock_wait_timeout_to_friendly_response(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1, 100);
        $booking = $this->bookSeat($attendee, $event, $seat);

        $pdo = new PDOException('Lock wait timeout exceeded');
        $pdo->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded'];
        $exception = new QueryException('mysql', 'select * from bookings for update', [], $pdo);

        DB::partialMock()
            ->shouldReceive('transaction')
            ->once()
            ->andThrow($exception);

        $this->actingAs($attendee)
            ->from(route('bookings.show', $booking))
            ->delete(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('error', DatabaseContention::BUSY_MESSAGE);
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
