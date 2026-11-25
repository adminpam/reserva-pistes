<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RefreshDatabase usa la conexión en memoria definida en Tests\TestCase.
class MapPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_database_has_an_explicit_empty_state(): void
    {
        Sport::query()->delete(); // Base realmente vacía: quitar el catálogo inicial de pádel.
        $this->getJson('/api/clubs')->assertOk()->assertExactJson([]);
        $this->get('/')->assertOk()
            ->assertSee('Todavía no hay deportes disponibles.')
            ->assertSee('rp-club-map', false)
            ->assertSee('brand/logo-original.png', false);
    }

    public function test_sport_filter_returns_only_matching_clubs_and_local_details(): void
    {
        $tennis = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $badminton = Sport::create(['name' => 'Bádminton', 'slug' => 'badminton', 'players_per_match' => 4]);
        $first = Club::create(['name' => 'Club de prueba A', 'lat' => 41.54, 'lng' => 2.1]);
        $second = Club::create(['name' => 'Club de prueba B', 'lat' => 41.4, 'lng' => 2.15]);
        foreach ([[$first, $tennis], [$second, $badminton]] as [$club, $sport]) {
            Court::create(['club_id' => $club->id, 'sport_id' => $sport->id, 'name' => 'Pista de prueba', 'price_per_hour' => 1000]);
        }

        $response = $this->getJson('/api/clubs?sport=tenis')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Club de prueba A')
            ->assertJsonPath('0.sports.0', 'Tenis')
            ->assertJsonPath('0.url', route('clubs.show', ['club' => $first->id, 'sports' => ['tenis']]));
        $this->assertStringEndsWith('/img/sports/tenis-thumb.jpg', $response->json('0.image_url'));
        $this->getJson('/api/clubs?sport[]=tenis')->assertUnprocessable();
    }
}
