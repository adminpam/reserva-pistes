<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Regresión de cancelar en otra pestaña. Cada caso usa SQLite en memoria.
class ClubAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private Club $club;
    private Court $court;
    private string $date = '2026-11-19';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-18 12:30:00'));
        $this->player = User::create(['name' => 'Persona de prueba', 'email' => 'availability@reserva.test', 'password' => 'test-password', 'role' => 'player']);
        $sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $this->club = Club::create(['name' => 'Club ficticio', 'lat' => 41.5, 'lng' => 2.1]);
        $this->court = Court::create(['club_id' => $this->club->id, 'sport_id' => $sport->id, 'name' => 'Pista prueba', 'price_per_hour' => 1150, 'open_hour' => 8, 'close_hour' => 22]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function url(?string $date = null): string
    {
        return '/clubs/'.$this->club->id.'/availability?date='.($date ?? $this->date);
    }

    private function isAvailable(array $data, int $hour): bool
    {
        $court = collect($data['courts'])->firstWhere('id', $this->court->id);

        return collect($court['hours'])->firstWhere('hour', $hour)['available'];
    }

    private function booking(string $start, string $end, ?Court $court = null): Booking
    {
        return Booking::create([
            'court_id' => ($court ?? $this->court)->id, 'user_id' => $this->player->id,
            'starts_at' => $start, 'ends_at' => $end, 'status' => 'confirmed',
            'is_open' => false, 'price_total' => 1150,
        ]);
    }

    public function test_reading_free_hours_does_not_expose_booking_or_player_data(): void
    {
        $response = $this->getJson($this->url())->assertOk()->assertJsonCount(1, 'courts');
        $this->assertCount(14, $response->json('courts.0.hours'));
        $this->assertTrue($this->isAvailable($response->json(), 10));
        $this->assertSame(['date', 'checked_at', 'courts'], array_keys($response->json()));
        $this->assertSame(['id', 'hours'], array_keys($response->json('courts.0')));
        $this->assertStringNotContainsString($this->player->email, $response->getContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_cancelling_frees_the_hour_on_the_next_read_and_preserves_history(): void
    {
        $booking = $this->booking($this->date.' 10:00:00', $this->date.' 11:00:00');
        $before = $this->getJson($this->url())->assertOk()->json();
        $this->assertFalse($this->isAvailable($before, 10));
        $this->assertTrue($this->isAvailable($before, 11));
        $this->actingAs($this->player)->patch('/bookings/'.$booking->id.'/cancel')->assertSessionHasNoErrors();
        $after = $this->getJson($this->url())->assertOk()->json();
        $this->assertTrue($this->isAvailable($after, 10));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled', 'price_total' => 1150]);
    }

    public function test_partial_overlap_blocks_both_intersecting_hours(): void
    {
        $this->booking($this->date.' 10:30:00', $this->date.' 11:30:00');
        $data = $this->getJson($this->url())->assertOk()->json();
        $this->assertTrue($this->isAvailable($data, 9));
        $this->assertFalse($this->isAvailable($data, 10));
        $this->assertFalse($this->isAvailable($data, 11));
        $this->assertTrue($this->isAvailable($data, 12));
    }

    public function test_a_booking_started_the_previous_day_can_block_the_first_hour(): void
    {
        $this->booking('2026-11-18 23:00:00', $this->date.' 09:00:00');
        $data = $this->getJson($this->url())->assertOk()->json();
        $this->assertFalse($this->isAvailable($data, 8));
        $this->assertTrue($this->isAvailable($data, 9));
    }

    public function test_hours_are_limited_to_the_requested_club(): void
    {
        $otherClub = Club::create(['name' => 'Otro club ficticio', 'lat' => 41.6, 'lng' => 2.2]);
        $otherCourt = Court::create(['club_id' => $otherClub->id, 'sport_id' => $this->court->sport_id, 'name' => 'Otra pista', 'price_per_hour' => 2000, 'open_hour' => 8, 'close_hour' => 22]);
        $this->booking($this->date.' 10:00:00', $this->date.' 11:00:00', $otherCourt);
        $response = $this->getJson($this->url())->assertOk()->assertJsonCount(1, 'courts');
        $this->assertSame($this->court->id, $response->json('courts.0.id'));
        $this->assertTrue($this->isAvailable($response->json(), 10));
    }

    public function test_hours_in_the_past_are_not_available(): void
    {
        $data = $this->getJson($this->url('2026-11-18'))->assertOk()->json();
        $this->assertFalse($this->isAvailable($data, 12));
        $this->assertTrue($this->isAvailable($data, 13));
    }

    public function test_invalid_dates_and_unknown_clubs_do_not_return_a_false_snapshot(): void
    {
        $this->getJson($this->url('2026-02-30'))->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->getJson($this->url('wrong'))->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->getJson('/clubs/'.$this->club->id.'/availability')->assertUnprocessable();
        $this->getJson('/clubs/999999/availability?date='.$this->date)->assertNotFound();
    }
}
