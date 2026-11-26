<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCheckout;
use Illuminate\Support\Str;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SportSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Club $mixed;
    private Club $badmintonClub;
    private Court $tennis;
    private Court $badminton;
    private Court $football;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $sports = [];
        foreach (['tenis' => 'Tenis', 'badminton' => 'Bádminton', 'futbol-sala' => 'Fútbol sala'] as $slug => $name) {
            $sports[$slug] = Sport::create(['name' => $name, 'slug' => $slug, 'players_per_match' => 4]);
        }
        $this->mixed = Club::create(['name' => 'Club Mixto Ficticio', 'lat' => 41.5, 'lng' => 2.1]);
        $this->badmintonClub = Club::create(['name' => 'Club Solo Bádminton', 'lat' => 41.4, 'lng' => 2.0]);
        foreach (['tenis' => 1200, 'badminton' => 900, 'futbol-sala' => 100] as $slug => $price) {
            $court = Court::create(['club_id' => $this->mixed->id, 'sport_id' => $sports[$slug]->id,
                'name' => 'Pista '.$slug, 'price_per_hour' => $price, 'is_outdoor' => $slug === 'futbol-sala']);
            $field = ['tenis' => 'tennis', 'badminton' => 'badminton', 'futbol-sala' => 'football'][$slug];
            $this->$field = $court;
        }
        Court::create(['club_id' => $this->badmintonClub->id, 'sport_id' => $sports['badminton']->id, 'name' => 'Otra pista', 'price_per_hour' => 1600]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function api(array $sports): string
    {
        return '/api/clubs?'.http_build_query(['sports' => $sports]);
    }

    public function test_no_filter_and_empty_list_mean_all_sports(): void
    {
        $this->getJson('/api/clubs')->assertOk()->assertJsonCount(2)->assertJsonPath('0.min_price_per_hour', 100);
        $this->getJson($this->api([]))->assertOk()->assertJsonCount(2);
    }

    public function test_multiple_sports_require_all_and_scope_price_image_and_tags(): void
    {
        $response = $this->getJson($this->api(['tenis', 'badminton']))->assertOk()->assertJsonCount(1);
        $mixed = collect($response->json())->firstWhere('id', $this->mixed->id);
        $this->assertSame(900, $mixed['min_price_per_hour']);
        $this->assertSame(2, $mixed['court_count']);
        $this->assertEqualsCanonicalizing(['tenis', 'badminton'], $mixed['sport_slugs']);
        $this->assertNotContains('Fútbol sala', $mixed['sports']);
        $this->assertNotEquals(asset('img/sports/futbol-sala-thumb.jpg'), $mixed['image_url']);
    }

    public function test_four_sports_without_a_common_club_return_empty_and_clearing_restores_all(): void
    {
        // Pádel existe por la migración, pero estos clubes no tienen ninguna pista suya.
        $this->getJson($this->api(['tenis', 'badminton', 'futbol-sala', 'padel']))
            ->assertOk()->assertExactJson([]);
        $this->getJson('/api/clubs')->assertOk()->assertJsonCount(2);
    }

    public function test_several_courts_of_one_sport_do_not_replace_a_missing_sport(): void
    {
        Court::create(['club_id' => $this->badmintonClub->id, 'sport_id' => $this->badminton->sport_id,
            'name' => 'Segunda pista de bádminton', 'price_per_hour' => 800]);
        $this->getJson($this->api(['tenis', 'badminton']))->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', $this->mixed->id);
    }

    public function test_adding_sports_only_narrows_the_club_catalog(): void
    {
        $this->getJson($this->api(['badminton']))->assertOk()->assertJsonCount(2);
        $this->getJson($this->api(['badminton', 'tenis']))->assertOk()->assertJsonCount(1);
        $this->getJson($this->api(['badminton', 'tenis', 'futbol-sala']))->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.min_price_per_hour', 100);
        $this->getJson($this->api(['badminton', 'tenis', 'futbol-sala', 'padel']))->assertOk()->assertExactJson([]);
    }

    public function test_single_legacy_sport_is_kept_in_the_club_link(): void
    {
        $r = $this->getJson('/api/clubs?sport=tenis')->assertOk()->assertJsonCount(1);
        parse_str(parse_url($r->json('0.url'), PHP_URL_QUERY), $query);
        $this->assertSame(['tenis'], $query['sports']);
        $this->get($r->json('0.url'))->assertOk()->assertSee('Pista tenis')->assertDontSee('Pista badminton');
    }

    public function test_duplicate_sports_are_canonicalized_without_duplicate_clubs(): void
    {
        $r = $this->getJson($this->api(['tenis', 'badminton', 'tenis']))->assertOk()->assertJsonCount(1);
        parse_str(parse_url($r->json('0.url'), PHP_URL_QUERY), $query);
        $this->assertSame(['badminton', 'tenis'], $query['sports']);
    }

    public function test_invalid_filters_are_validation_errors(): void
    {
        foreach ([['sports' => 'tenis'], ['sports' => ['missing']], ['sports' => [['tenis']]],
            ['sports' => ['x' => 'tenis']], ['sports' => array_fill(0, 33, 'tenis')],
            ['sports' => ['tenis'], 'sport' => 'badminton'], ['sport' => ['tenis']]] as $query) {
            $this->getJson('/api/clubs?'.http_build_query($query))->assertUnprocessable();
        }
    }

    public function test_club_courts_weather_and_date_form_use_the_same_filter(): void
    {
        $url = route('clubs.show', ['club' => $this->mixed, 'date' => '2026-11-25', 'sports' => ['tenis', 'badminton']]);
        $this->get($url)->assertOk()->assertSee('Pista tenis')->assertSee('Pista badminton')->assertDontSee('Pista futbol-sala')
            ->assertSee('name="sports[]"', false)->assertSee('value="badminton"', false)->assertDontSee('id="weather"', false);
    }

    public function test_public_availability_returns_only_selected_court_ids_without_private_data(): void
    {
        $url = route('clubs.availability', ['club' => $this->mixed, 'date' => '2026-11-25', 'sports' => ['tenis']]);
        $r = $this->getJson($url)->assertOk()->assertJsonCount(1, 'courts')->assertJsonPath('courts.0.id', $this->tennis->id);
        $this->assertSame(['date', 'checked_at', 'courts'], array_keys($r->json()));
        $this->assertSame(['id', 'hours'], array_keys($r->json('courts.0')));
        $this->getJson(route('clubs.availability', ['club' => $this->mixed, 'date' => '2026-11-25']))->assertJsonCount(3, 'courts');
    }

    public function test_a_filter_with_no_courts_has_a_specific_empty_state(): void
    {
        $this->get(route('clubs.show', ['club' => $this->badmintonClub, 'sports' => ['tenis']]))
            ->assertOk()->assertSee('No hay pistas para estos deportes en este club')->assertDontSee('Otra pista');
    }

    public function test_filter_does_not_hide_a_hold_or_confirmed_booking_in_availability(): void
    {
        $owner = User::factory()->create();
        Booking::create(['user_id' => $owner->id, 'court_id' => $this->tennis->id,
            'starts_at' => '2026-11-25 10:00:00', 'ends_at' => '2026-11-25 11:00:00', 'status' => 'confirmed', 'price_total' => 1200]);
        BookingCheckout::create(['id' => (string) Str::uuid(), 'user_id' => $owner->id, 'court_id' => $this->tennis->id,
            'starts_at' => '2026-11-25 11:00:00', 'ends_at' => '2026-11-25 12:00:00',
            'expires_at' => now()->addMinutes(5), 'amount_cents' => 1200, 'status' => 'pending']);
        $r = $this->getJson(route('clubs.availability', ['club' => $this->mixed, 'date' => '2026-11-25', 'sports' => ['tenis']]))->assertOk();
        $hours = collect($r->json('courts.0.hours'));
        $this->assertFalse($hours->firstWhere('hour', 10)['available']);
        $this->assertFalse($hours->firstWhere('hour', 11)['available']);
        $this->assertTrue($hours->firstWhere('hour', 12)['available']);
    }

    public function test_match_search_accepts_multiple_sports_and_keeps_other_filters(): void
    {
        $owner = User::factory()->create();
        foreach ([$this->tennis, $this->badminton, $this->football] as $court) {
            $booking = Booking::create(['user_id' => $owner->id, 'court_id' => $court->id,
                'starts_at' => '2026-11-25 10:00:00', 'ends_at' => '2026-11-25 11:00:00',
                'status' => 'confirmed', 'is_open' => true, 'price_total' => 1200]);
            $booking->players()->attach($owner->id);
        }
        $this->actingAs($owner)->get('/matches?'.http_build_query(['sports' => ['tenis', 'badminton'], 'club' => 'Mixto']))
            ->assertOk()->assertViewHas('matches', fn ($matches) => $matches->count() === 2);
    }
}
