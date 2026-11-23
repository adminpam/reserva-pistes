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

// Comprueba plazas, niveles, permisos e historial mediante las rutas reales.
// La concurrencia entre procesos en PostgreSQL se verifica aparte del test SQLite.
class MatchRulesTest extends TestCase
{
    use RefreshDatabase;

    private Court $court;
    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->organizer = User::factory()->create(['name' => 'Organizador ficticio', 'email' => 'organizer@example.test']);
        $sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $club = Club::create(['name' => 'Club ficticio', 'lat' => 41.5, 'lng' => 2.1]);
        $this->court = Court::create(['club_id' => $club->id, 'sport_id' => $sport->id, 'name' => 'Pista ficticia', 'price_per_hour' => 1400, 'open_hour' => 8, 'close_hour' => 22]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function createMatch(array $changes = []): Booking
    {
        $booking = Booking::create(array_replace([
            'court_id' => $this->court->id, 'user_id' => $this->organizer->id,
            'starts_at' => '2026-11-25 10:00:00', 'ends_at' => '2026-11-25 11:00:00',
            'price_total' => 1400, 'is_open' => true, 'status' => 'confirmed',
        ], $changes));
        if ($booking->is_open) {
            $booking->players()->attach($this->organizer->id);
        }

        return $booking;
    }

    public function test_last_place_is_available_once_and_repeated_join_is_an_error(): void
    {
        $match = $this->createMatch();
        $player = User::factory()->create();
        $this->actingAs($player)->post('/matches/'.$match->id.'/join')->assertSessionHasNoErrors();
        $this->post('/matches/'.$match->id.'/join')->assertSessionHasErrors('match');
        $this->actingAs(User::factory()->create())->post('/matches/'.$match->id.'/join')->assertSessionHasErrors('match');
        $this->assertSame(2, $match->players()->count());
        $this->assertSame(0, $match->fresh()->freeSlots());
    }

    public function test_capacity_comes_from_the_sport_instead_of_a_fixed_player_count(): void
    {
        $this->court->sport->update(['players_per_match' => 6]);
        $match = $this->createMatch();
        foreach (User::factory()->count(5)->create() as $player) {
            $this->actingAs($player)->post('/matches/'.$match->id.'/join')->assertSessionHasNoErrors();
        }
        $this->actingAs(User::factory()->create())->post('/matches/'.$match->id.'/join')->assertSessionHasErrors('match');
        $this->assertSame(6, $match->players()->count());
    }

    public function test_missing_or_incompatible_level_cannot_join_a_restricted_match(): void
    {
        $match = $this->createMatch(['min_level' => 2, 'max_level' => 4]);
        $unknown = User::factory()->create();
        $outside = User::factory()->create();
        UserSportLevel::create(['user_id' => $outside->id, 'sport_id' => $this->court->sport_id, 'level' => 5]);
        foreach ([$unknown, $outside] as $player) {
            $this->actingAs($player)->post('/matches/'.$match->id.'/join')->assertSessionHasErrors('match');
        }
        $this->assertSame(1, $match->players()->count());
    }

    public function test_matching_level_can_join_and_is_visible_in_my_matches(): void
    {
        $match = $this->createMatch(['min_level' => 2, 'max_level' => 4]);
        $player = User::factory()->create();
        UserSportLevel::create(['user_id' => $player->id, 'sport_id' => $this->court->sport_id, 'level' => 3]);
        $this->actingAs($player)->post('/matches/'.$match->id.'/join')->assertSessionHasNoErrors();
        $this->get('/my/matches')->assertOk()->assertSee('Club ficticio');
        $this->assertTrue($match->players()->where('users.id', $player->id)->exists());
    }

    public function test_participant_can_leave_and_another_player_can_take_the_place(): void
    {
        $match = $this->createMatch();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $match->players()->attach($first->id);
        $this->actingAs($first)->delete('/matches/'.$match->id.'/leave')->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertSame(1, $match->players()->count());
        $this->actingAs($second)->post('/matches/'.$match->id.'/join')->assertSessionHasNoErrors();
        $this->assertSame(2, $match->players()->count());
    }

    public function test_organizer_leaving_cancels_the_match_and_keeps_its_participants(): void
    {
        $match = $this->createMatch();
        $other = User::factory()->create();
        $match->players()->attach($other->id);
        $this->actingAs($this->organizer)->delete('/matches/'.$match->id.'/leave')->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $match->fresh()->status);
        $this->assertSame(2, $match->players()->count());
        $this->assertFalse(Booking::overlaps($this->court->id, $match->starts_at, $match->ends_at));
        $this->actingAs($other)->get('/my/matches')->assertOk()->assertSee('Cancelado');
    }

    public function test_non_participant_cannot_leave_or_cancel_another_players_match(): void
    {
        $match = $this->createMatch();
        $this->actingAs(User::factory()->create())->delete('/matches/'.$match->id.'/leave')->assertForbidden();
        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertSame(1, $match->players()->count());
    }

    public function test_private_booking_is_not_a_match_even_if_its_url_is_submitted(): void
    {
        $booking = $this->createMatch(['is_open' => false]);
        $this->actingAs($this->organizer)->delete('/matches/'.$booking->id.'/leave')->assertSessionHasErrors('match');
        $this->actingAs(User::factory()->create())->post('/matches/'.$booking->id.'/join')->assertSessionHasErrors('match');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_cancelled_match_cannot_be_joined_or_lose_its_history(): void
    {
        $match = $this->createMatch(['status' => 'cancelled']);
        $player = User::factory()->create();
        $match->players()->attach($player->id);
        $this->actingAs($player)->delete('/matches/'.$match->id.'/leave')->assertSessionHasErrors('match');
        $this->actingAs(User::factory()->create())->post('/matches/'.$match->id.'/join')->assertSessionHasErrors('match');
        $this->assertSame(2, $match->players()->count());
    }

    public function test_started_match_cannot_be_joined_left_or_cancelled(): void
    {
        $match = $this->createMatch(['starts_at' => '2026-11-24 11:00:00', 'ends_at' => '2026-11-24 13:00:00']);
        $player = User::factory()->create();
        $match->players()->attach($player->id);
        foreach ([$this->organizer, $player] as $participant) {
            $this->actingAs($participant)->delete('/matches/'.$match->id.'/leave')->assertSessionHasErrors('match');
        }
        $this->actingAs(User::factory()->create())->post('/matches/'.$match->id.'/join')->assertSessionHasErrors('match');
        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertSame(2, $match->players()->count());
    }

    public function test_search_combines_club_sport_and_my_level_filters(): void
    {
        $this->createMatch(['min_level' => 2, 'max_level' => 4]);
        $other = Club::create(['name' => 'Otro club ficticio', 'lat' => 41.4, 'lng' => 2.2]);
        $court = Court::create(['club_id' => $other->id, 'sport_id' => $this->court->sport_id, 'name' => 'Pista diferente', 'price_per_hour' => 1200]);
        $this->createMatch(['court_id' => $court->id, 'min_level' => 4, 'max_level' => 5]);
        $player = User::factory()->create();
        UserSportLevel::create(['user_id' => $player->id, 'sport_id' => $this->court->sport_id, 'level' => 3]);
        $this->actingAs($player)->get('/matches?sport=tenis&club=Club&my_level=1')
            ->assertOk()->assertSee('Club ficticio')->assertDontSee('Otro club ficticio');
    }

    public function test_malformed_search_filters_are_validation_errors(): void
    {
        $this->actingAs($this->organizer)->getJson('/matches?club[]=x')->assertUnprocessable()->assertJsonValidationErrors('club');
        $this->getJson('/matches?sport[]=x')->assertUnprocessable()->assertJsonValidationErrors('sport');
    }

    public function test_guest_cannot_join_or_leave(): void
    {
        $match = $this->createMatch();
        $this->post('/matches/'.$match->id.'/join')->assertRedirect(route('login'));
        $this->delete('/matches/'.$match->id.'/leave')->assertRedirect(route('login'));
        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertSame(1, $match->players()->count());
    }
}
