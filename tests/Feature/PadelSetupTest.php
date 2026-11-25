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
use Illuminate\Support\Str;
use Tests\TestCase;

class PadelSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_registers_padel_for_four_and_does_not_overwrite_a_managed_sport(): void
    {
        $sport = Sport::where('slug', 'padel')->sole();
        $this->assertSame('Pádel', $sport->name);
        $this->assertEquals(4, $sport->players_per_match);
        $sport->update(['players_per_match' => 6]);
        $migration = require database_path('migrations/2026_11_24_000002_add_padel_demo.php');
        $migration->up();
        $this->assertEquals(6, $sport->fresh()->players_per_match);
        $this->assertSame(1, Sport::where('slug', 'padel')->count());
    }

    public function test_demo_setup_is_additive_limited_and_repeatable(): void
    {
        $sport = Sport::where('slug', 'padel')->sole();
        $clubs = collect();
        foreach (range(1, 4) as $i) $clubs->push(Club::create(['name' => 'Club demo '.$i, 'lat' => 41.5, 'lng' => 2.1]));
        $existing = Court::create(['club_id' => $clubs[0]->id, 'sport_id' => $sport->id, 'name' => 'Pádel administrado', 'price_per_hour' => 999]);
        $migration = require database_path('migrations/2026_11_24_000002_add_padel_demo.php');
        $migration->up();
        $migration->up();
        $this->assertSame(3, Court::where('sport_id', $sport->id)->count());
        $this->assertEquals(999, $existing->fresh()->price_per_hour);
        $this->assertSame(0, $clubs[3]->courts()->count());
        $migration->down();
        $this->assertDatabaseHas('courts', ['id' => $existing->id]);
    }

    public function test_padel_has_its_own_illustration_in_map_and_profile_level(): void
    {
        $sport = Sport::where('slug', 'padel')->sole();
        $club = Club::create(['name' => 'Club Pádel ficticio', 'lat' => 41.5, 'lng' => 2.1]);
        Court::create(['club_id' => $club->id, 'sport_id' => $sport->id, 'name' => 'Pádel demo', 'price_per_hour' => 1400]);
        $this->getJson('/api/clubs?sports[]=padel')->assertOk()->assertJsonPath('0.image_url', asset('img/sports/padel.webp'));
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/profile', ['name' => 'Persona Demo', 'levels' => [$sport->id => 3]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $user->id, 'sport_id' => $sport->id, 'level' => 3]);
        $this->get('/profile')->assertOk()->assertSee('Pádel');
    }

    public function test_paid_padel_match_uses_four_places_and_preserves_refund_history(): void
    {
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        try {
            $sport = Sport::where('slug', 'padel')->sole();
            $club = Club::create(['name' => 'Club Pádel ficticio', 'lat' => 41.5, 'lng' => 2.1]);
            $court = Court::create(['club_id' => $club->id, 'sport_id' => $sport->id, 'name' => 'Pádel demo', 'price_per_hour' => 1400]);
            $organizer = User::factory()->create();
            UserSportLevel::create(['user_id' => $organizer->id, 'sport_id' => $sport->id, 'level' => 3]);
            $key = (string) Str::uuid();
            $this->actingAs($organizer)->post('/bookings', ['checkout_key' => $key, 'court_id' => $court->id,
                'date' => '2026-11-25', 'hour' => 10, 'is_open' => 1, 'min_level' => 2, 'max_level' => 4])->assertSessionHasNoErrors();
            $this->post(route('checkout.pay', $key), ['payment' => ['holder' => 'Persona Demo', 'number' => '4242424242424242', 'expiry' => '12/30', 'cvc' => '123']])->assertSessionHasNoErrors();
            $match = Booking::sole();
            foreach (range(1, 3) as $i) {
                $player = User::factory()->create();
                UserSportLevel::create(['user_id' => $player->id, 'sport_id' => $sport->id, 'level' => 3]);
                $this->actingAs($player)->post(route('matches.join', $match))->assertSessionHasNoErrors();
            }
            $this->actingAs(User::factory()->create())->postJson(route('matches.join', $match))->assertUnprocessable();
            $this->assertSame(4, $match->players()->count());
            $this->actingAs($organizer)->delete(route('matches.leave', $match))->assertSessionHasNoErrors();
            $this->assertSame('cancelled', $match->fresh()->status);
            $this->assertSame('refunded', $match->checkout->payment->fresh()->status);
            $this->assertSame(4, $match->players()->count());
        } finally {
            $this->travelBack();
        }
    }
}
