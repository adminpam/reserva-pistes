<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Sport;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendSportsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sport::firstOrCreate(['slug' => 'padel'], ['name' => 'Pádel', 'players_per_match' => 4]);
        Sport::create(['slug' => 'tenis', 'name' => 'Tenis', 'players_per_match' => 2]);
        Sport::create(['slug' => 'badminton', 'name' => 'Bádminton', 'players_per_match' => 4]);
    }

    private function selected(string $html, string $mode): array
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $nodes = (new DOMXPath($doc))->query('//*[@data-sport-filter="'.$mode.'"]//input[@name="sports[]" and @type="checkbox" and @checked]');
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->getAttribute('value');
        }
        sort($values);

        return $values;
    }

    public function test_map_checks_all_requested_sports_on_first_render(): void
    {
        $response = $this->get('/?'.http_build_query(['sports' => ['tenis', 'padel']]));
        $response->assertOk()->assertViewHas('selectedSportSlugs', ['padel', 'tenis']);
        $this->assertSame(['padel', 'tenis'], $this->selected($response->getContent(), 'map'));
        $response->assertSee('ui/sport-selection.css', false)->assertSee('data-sport-all', false);
    }

    public function test_map_handles_all_and_legacy_single_sport_links(): void
    {
        $all = $this->get('/')->assertOk();
        $this->assertSame([], $this->selected($all->getContent(), 'map'));
        $legacy = $this->get('/?sport=tenis')->assertOk();
        $this->assertSame(['tenis'], $this->selected($legacy->getContent(), 'map'));
    }

    public function test_map_rejects_unknown_and_conflicting_filters_before_rendering(): void
    {
        $this->getJson('/?sports[]=desconocido')->assertUnprocessable();
        $this->getJson('/?sport=tenis&sports[]=padel')->assertUnprocessable();
    }

    public function test_match_get_form_preserves_sports_club_and_level_without_javascript(): void
    {
        $player = User::factory()->create();
        $response = $this->actingAs($player)->get('/matches?'.http_build_query([
            'sports' => ['tenis', 'padel'], 'club' => 'Club de prueba', 'my_level' => 1,
        ]))->assertOk();
        $this->assertSame(['padel', 'tenis'], $this->selected($response->getContent(), 'submit'));
        $response->assertSee('value="Club de prueba"', false)->assertSee('name="my_level" value="1" checked', false)
            ->assertSee('method="GET"', false)->assertDontSee('<select name="sport"', false);
    }

    public function test_club_return_and_empty_state_links_preserve_the_selection(): void
    {
        $club = Club::create(['name' => 'Club sin pistas', 'lat' => 41.5, 'lng' => 2.1]);
        $response = $this->get(route('clubs.show', ['club' => $club, 'sports' => ['tenis', 'padel']]))->assertOk();
        $expected = e(route('home', ['sports' => ['padel', 'tenis']]));
        $response->assertSee('href="'.$expected.'"', false)->assertSee('Volver al mapa');
        $this->assertSame(2, substr_count($response->getContent(), 'href="'.$expected.'"'));
    }
}
