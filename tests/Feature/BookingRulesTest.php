<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use App\Models\UserSportLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Casos de negocio enviados por HTTP, con usuarios ficticios y base en memoria.
class BookingRulesTest extends TestCase
{
    use RefreshDatabase;

    private Court $court;
    private User $player;
    private string $date = '2026-11-25';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->player = User::factory()->create(['name' => 'Jugadora ficticia', 'email' => 'booking-player@example.test']);
        $sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $club = Club::create(['name' => 'Club ficticio', 'lat' => 41.5, 'lng' => 2.1]);
        $this->court = Court::create(['club_id' => $club->id, 'sport_id' => $sport->id, 'name' => 'Pista ficticia', 'price_per_hour' => 1450, 'open_hour' => 8, 'close_hour' => 22]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['court_id' => $this->court->id, 'date' => $this->date, 'hour' => 10], $changes);
    }

    private function existing(string $start, string $end, bool $open = false): Booking
    {
        return Booking::create(['court_id' => $this->court->id, 'user_id' => $this->player->id,
            'starts_at' => $start, 'ends_at' => $end, 'price_total' => 1450,
            'is_open' => $open, 'status' => 'confirmed']);
    }

    public function test_price_owner_and_status_come_from_the_server(): void
    {
        $other = User::factory()->create();
        $this->actingAs($this->player)->post('/bookings', $this->payload([
            'price_total' => 1, 'user_id' => $other->id, 'status' => 'cancelled',
        ]))->assertRedirect(route('bookings.index'))->assertSessionHasNoErrors();

        $booking = Booking::sole();
        $this->assertSame($this->player->id, $booking->user_id);
        $this->assertEquals(1450, $booking->price_total);
        $this->assertSame('confirmed', $booking->status);
        $this->assertEquals(60, $booking->starts_at->diffInMinutes($booking->ends_at));
        $this->court->update(['price_per_hour' => 9900]);
        $this->assertEquals(1450, $booking->fresh()->price_total);
    }

    public function test_open_match_without_level_keys_counts_the_organizer_once(): void
    {
        $this->actingAs($this->player)->post('/bookings', $this->payload(['is_open' => 1]))
            ->assertRedirect(route('matches.mine'))->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->assertNull($booking->min_level);
        $this->assertNull($booking->max_level);
        $this->assertSame(1, $booking->players()->count());
        $this->assertTrue($booking->players()->where('users.id', $this->player->id)->exists());
    }

    public function test_a_match_accepts_only_a_maximum_level(): void
    {
        UserSportLevel::create(['user_id' => $this->player->id, 'sport_id' => $this->court->sport_id, 'level' => 2]);
        $this->actingAs($this->player)->post('/bookings', $this->payload(['is_open' => 1, 'max_level' => 3]))
            ->assertSessionHasNoErrors();
        $this->assertNull(Booking::sole()->min_level);
        $this->assertEquals(3, Booking::sole()->max_level);
    }

    public function test_a_match_accepts_only_a_minimum_level(): void
    {
        UserSportLevel::create(['user_id' => $this->player->id, 'sport_id' => $this->court->sport_id, 'level' => 4]);
        $this->actingAs($this->player)->post('/bookings', $this->payload(['is_open' => 1, 'min_level' => 3]))
            ->assertSessionHasNoErrors();
        $this->assertEquals(3, Booking::sole()->min_level);
        $this->assertNull(Booking::sole()->max_level);
    }

    public function test_an_inverted_level_range_does_not_create_a_booking(): void
    {
        $this->actingAs($this->player)->post('/bookings', $this->payload(['is_open' => 1, 'min_level' => 4, 'max_level' => 2]))
            ->assertSessionHasErrors('max_level');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_organizer_must_have_a_level_within_the_requested_range(): void
    {
        $this->actingAs($this->player)->post('/bookings', $this->payload(['is_open' => 1, 'min_level' => 3]))
            ->assertSessionHasErrors('min_level');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_players', 0);
    }

    public function test_private_booking_ignores_hidden_level_fields(): void
    {
        $this->actingAs($this->player)->post('/bookings', $this->payload(['min_level' => 5, 'max_level' => 1]))
            ->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->assertFalse($booking->is_open);
        $this->assertNull($booking->min_level);
        $this->assertNull($booking->max_level);
        $this->assertSame(0, $booking->players()->count());
    }

    public function test_closed_hours_are_rejected(): void
    {
        foreach ([7, 22] as $hour) {
            $this->actingAs($this->player)->post('/bookings', $this->payload(['hour' => $hour]))
                ->assertSessionHasErrors('hour');
        }
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_current_hour_and_past_dates_are_rejected(): void
    {
        $this->actingAs($this->player)->post('/bookings', $this->payload(['date' => '2026-11-24', 'hour' => 12]))
            ->assertSessionHasErrors('hour');
        $this->post('/bookings', $this->payload(['date' => '2026-11-23']))->assertSessionHasErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_date_must_be_a_calendar_day_without_a_time(): void
    {
        $this->actingAs($this->player)->postJson('/bookings', $this->payload(['date' => '2026-11-25 10:30:00']))
            ->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_partial_overlap_blocks_both_hours_but_allows_the_next_one(): void
    {
        $this->existing('2026-11-25 10:30:00', '2026-11-25 11:30:00');
        foreach ([10, 11] as $hour) {
            $this->actingAs($this->player)->post('/bookings', $this->payload(['hour' => $hour]))->assertSessionHasErrors('hour');
        }
        $this->post('/bookings', $this->payload(['hour' => 12]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_another_player_cannot_cancel_a_booking(): void
    {
        $booking = $this->existing('2026-11-25 10:00:00', '2026-11-25 11:00:00');
        $this->actingAs(User::factory()->create())->patch('/bookings/'.$booking->id.'/cancel')->assertForbidden();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_owner_cancellation_is_repeatable_and_preserves_the_record(): void
    {
        $booking = $this->existing('2026-11-25 10:00:00', '2026-11-25 11:00:00');
        $this->actingAs($this->player)->patch('/bookings/'.$booking->id.'/cancel')->assertSessionHasNoErrors();
        $this->patch('/bookings/'.$booking->id.'/cancel')->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_owner_cannot_cancel_a_started_booking(): void
    {
        $booking = $this->existing('2026-11-24 11:00:00', '2026-11-24 12:00:00');
        $this->actingAs($this->player)->patch('/bookings/'.$booking->id.'/cancel')->assertSessionHasErrors('booking');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_guest_cannot_create_a_booking(): void
    {
        $this->post('/bookings', $this->payload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('bookings', 0);
    }
}
