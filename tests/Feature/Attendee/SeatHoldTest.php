<?php

namespace Tests\Feature\Attendee;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\User;
use App\Services\SeatHoldService;
use App\Support\DatabaseContention;
use Illuminate\Database\QueryException;
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
class SeatHoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_gets_forbidden_on_hold_routes(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->actingAs($organizer)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertForbidden();

        $this->actingAs($organizer)
            ->deleteJson(route('events.holds.destroy', $event))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_hold_write_routes(): void
    {
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->post(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertRedirect(route('login'));

        $this->delete(route('events.holds.destroy', $event))
            ->assertRedirect(route('login'));
    }

    public function test_attendee_can_hold_available_seats_for_about_ten_minutes(): void
    {
        $this->freezeTime();

        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seatA = $this->makeSeat($event, 'A1', 1, 150);
        $seatB = $this->makeSeat($event, 'A2', 2, 150);

        $response = $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), [
                'seat_ids' => [$seatA->id, $seatB->id],
            ]);

        $response->assertCreated();

        $this->assertDatabaseCount('seat_holds', 2);
        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seatA->id,
            'user_id' => $attendee->id,
            'expires_at' => now()->addMinutes(SeatHoldService::HOLD_MINUTES),
        ]);
        $this->assertSame('held', $seatA->fresh()->status);
        $this->assertSame('held', $seatB->fresh()->status);
    }

    public function test_second_user_cannot_hold_the_same_seat(): void
    {
        // Sequential conflict check (true concurrent lock races need MySQL).
        $first = User::factory()->attendee()->create();
        $second = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->actingAs($first)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($second)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertStatus(409)
            ->assertJsonFragment(['message' => SeatHoldService::CONFLICT_MESSAGE]);

        $this->assertSame(1, SeatHold::where('seat_id', $seat->id)->count());
        $this->assertSame($first->id, SeatHold::where('seat_id', $seat->id)->value('user_id'));
    }

    public function test_more_than_six_seats_in_one_request_are_rejected(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seatIds = [];

        for ($i = 1; $i <= 7; $i++) {
            $seatIds[] = $this->makeSeat($event, 'A'.$i, $i)->id;
        }

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => $seatIds])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seat_ids');

        $this->assertDatabaseCount('seat_holds', 0);
    }

    public function test_holding_more_than_six_seats_total_for_an_event_is_rejected(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $firstBatch = [];
        $secondBatch = [];

        for ($i = 1; $i <= 6; $i++) {
            $firstBatch[] = $this->makeSeat($event, 'A'.$i, $i)->id;
        }
        for ($i = 7; $i <= 8; $i++) {
            $secondBatch[] = $this->makeSeat($event, 'A'.$i, $i)->id;
        }

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => $firstBatch])
            ->assertCreated();

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => $secondBatch])
            ->assertStatus(409);

        $this->assertSame(6, SeatHold::where('user_id', $attendee->id)->count());
    }

    public function test_seat_from_another_event_is_rejected(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $foreignSeat = $this->makeSeat($otherEvent, 'A1', 1);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$foreignSeat->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seat_ids');

        $this->assertDatabaseCount('seat_holds', 0);
    }

    public function test_expired_hold_can_be_taken_by_another_user(): void
    {
        $this->freezeTime();

        $first = User::factory()->attendee()->create();
        $second = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->actingAs($first)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->travel(11)->minutes();

        $this->actingAs($second)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seat->id,
            'user_id' => $second->id,
        ]);
        $this->assertDatabaseMissing('seat_holds', [
            'seat_id' => $seat->id,
            'user_id' => $first->id,
        ]);
    }

    public function test_attendee_can_release_own_holds(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($attendee)
            ->deleteJson(route('events.holds.destroy', $event))
            ->assertOk();

        $this->assertDatabaseCount('seat_holds', 0);
        $this->assertSame('available', $seat->fresh()->status);
    }

    public function test_holds_release_expired_command_removes_only_expired_holds(): void
    {
        $this->freezeTime();

        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $expiredSeat = $this->makeSeat($event, 'A1', 1);
        $activeSeat = $this->makeSeat($event, 'A2', 2);

        SeatHold::create([
            'seat_id' => $expiredSeat->id,
            'user_id' => $attendee->id,
            'expires_at' => now()->subMinute(),
        ]);
        Seat::whereKey($expiredSeat->id)->update(['status' => 'held']);

        SeatHold::create([
            'seat_id' => $activeSeat->id,
            'user_id' => $attendee->id,
            'expires_at' => now()->addMinutes(5),
        ]);
        Seat::whereKey($activeSeat->id)->update(['status' => 'held']);

        $this->artisan('holds:release-expired')
            ->expectsOutput('Released 1 expired hold(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('seat_holds', ['seat_id' => $expiredSeat->id]);
        $this->assertDatabaseHas('seat_holds', ['seat_id' => $activeSeat->id]);
        $this->assertSame('available', $expiredSeat->fresh()->status);
        $this->assertSame('held', $activeSeat->fresh()->status);
    }

    public function test_fast_fail_returns_409_without_locking(): void
    {
        $holder = User::factory()->attendee()->create();
        $challenger = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->actingAs($holder)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($challenger)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertStatus(409)
            ->assertJson(['message' => SeatHoldService::CONFLICT_MESSAGE]);

        $sql = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        $this->assertStringNotContainsStringIgnoringCase('for update', $sql);
    }

    public function test_deadlock_query_exception_is_mapped_to_friendly_response(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $pdo = new PDOException('Deadlock found when trying to get lock');
        $pdo->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock'];
        $exception = new QueryException('mysql', 'select * from seats where id = ? for update', [], $pdo);

        DB::partialMock()
            ->shouldReceive('transaction')
            ->once()
            ->andThrow($exception);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertStatus(503)
            ->assertJson(['message' => DatabaseContention::BUSY_MESSAGE]);
    }

    public function test_seats_json_reflects_hold_after_cache_invalidation(): void
    {
        $attendee = User::factory()->attendee()->create();
        $other = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        $this->actingAs($other)
            ->getJson(route('events.seats', $event))
            ->assertOk()
            ->assertJsonFragment(['id' => $seat->id, 'state' => 'available']);

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertCreated();

        $this->actingAs($attendee)
            ->getJson(route('events.seats', $event))
            ->assertOk()
            ->assertJsonFragment(['id' => $seat->id, 'state' => 'held_by_me']);

        $this->actingAs($other)
            ->getJson(route('events.seats', $event))
            ->assertOk()
            ->assertJsonFragment(['id' => $seat->id, 'state' => 'held_by_others']);
    }

    public function test_seat_hold_rate_limiter_returns_429_after_limit(): void
    {
        $attendee = User::factory()->attendee()->create();
        $event = Event::factory()->create();
        $seat = $this->makeSeat($event, 'A1', 1);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($attendee)
                ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
                ->assertSuccessful();
        }

        $this->actingAs($attendee)
            ->postJson(route('events.holds.store', $event), ['seat_ids' => [$seat->id]])
            ->assertStatus(429)
            ->assertJson([
                'message' => 'Too many hold attempts. Please wait a moment and try again.',
            ]);
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
