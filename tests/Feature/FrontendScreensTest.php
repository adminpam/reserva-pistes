<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Renderiza las pantallas con datos ficticios aislados. Nunca migra la base local.
class FrontendScreensTest extends TestCase
{
    private User $player;
    private User $manager;
    private User $admin;
    private Sport $sport;
    private Club $club;
    private Court $court;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        $this->withoutVite();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->player = User::create(['name' => 'Persona Prueba', 'email' => 'player@frontend.test', 'password' => 'password1234', 'role' => 'player']);
        $this->manager = User::create(['name' => 'Gestor Prueba', 'email' => 'manager@frontend.test', 'password' => 'password1234', 'role' => 'manager']);
        $this->admin = User::create(['name' => 'Admin Prueba', 'email' => 'admin@frontend.test', 'password' => 'password1234', 'role' => 'admin']);
        $this->sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $this->club = Club::create(['name' => 'Club de prueba visual', 'lat' => 41.54, 'lng' => 2.1, 'manager_id' => $this->manager->id]);
        $this->court = Court::create(['club_id' => $this->club->id, 'sport_id' => $this->sport->id, 'name' => 'Pista de prueba', 'price_per_hour' => 1000, 'open_hour' => 8, 'close_hour' => 22]);
    }

    private function booking(int $days, bool $open = false, string $status = 'confirmed'): Booking
    {
        $start = now()->addDays($days)->setTime(10, 0);
        $booking = Booking::create([
            'court_id' => $this->court->id, 'user_id' => $this->player->id,
            'starts_at' => $start, 'ends_at' => $start->copy()->addHour(),
            'status' => $status, 'is_open' => $open, 'price_total' => 1000,
        ]);
        if ($open) $booking->players()->attach($this->player->id);

        return $booking;
    }

    public function test_player_pages_render_populated_and_cancelled_states(): void
    {
        $this->booking(2);
        $this->booking(-2);
        $this->booking(3, false, 'cancelled');
        $this->booking(4, true);
        $this->booking(-4, true);
        $this->booking(5, true, 'cancelled');
        $this->actingAs($this->player);

        $this->get('/my/bookings')->assertOk()->assertSee('Confirmada')->assertSee('Jugada')->assertSee('Cancelada');
        $this->get('/my/matches')->assertOk()->assertSee('Organizas tú')->assertSee('Jugado')->assertSee('Cancelado');
        $this->get('/matches')->assertOk()->assertSee('1/2 jugadores')->assertSee('Apuntado');
        $this->get('/profile')->assertOk()->assertSee('Mis niveles')->assertSee('No juego');
        $this->get('/clubs/'.$this->club->id)->assertOk()->assertSee('rp-slot-grid', false)->assertSee('Reservar');
    }

    public function test_empty_player_lists_show_only_the_empty_state(): void
    {
        $this->actingAs($this->player);
        $this->get('/my/bookings')->assertOk()->assertSee('Todavía no tienes reservas')->assertDontSee('rp-record__image', false);
        $this->get('/my/matches')->assertOk()->assertSee('Aún no tienes partidos');
        $this->get('/matches')->assertOk()->assertSee('No hay partidos con esos filtros');
    }

    public function test_profile_and_booking_forms_keep_the_existing_contract(): void
    {
        $this->actingAs($this->player);
        $this->patch('/profile', ['name' => 'Nombre de Prueba', 'levels' => [$this->sport->id => 3]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->sport->id, 'level' => 3]);
        $this->post('/bookings', ['court_id' => $this->court->id, 'date' => now()->addDays(2)->toDateString(), 'hour' => 11, 'is_open' => 0, 'min_level' => null, 'max_level' => null])->assertRedirect(route('bookings.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['user_id' => $this->player->id, 'court_id' => $this->court->id, 'status' => 'confirmed']);
    }

    public function test_manager_and_admin_screens_render_real_forms(): void
    {
        $this->booking(2);
        $this->actingAs($this->manager);
        foreach (['/manager/courts', '/manager/courts/create', '/manager/courts/'.$this->court->id.'/edit', '/manager/bookings'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($this->admin);
        foreach (['/admin/clubs', '/admin/sports', '/admin/users'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/users')->assertSee('Tu cuenta');
    }

    public function test_error_pages_and_authentication_pages_render_without_screen_overrides(): void
    {
        $this->get('/pagina-que-no-existe')->assertNotFound()->assertSee('Página no encontrada');
        $this->get('/login')->assertOk()->assertDontSee('ui/screens.css', false);
        $this->get('/register')->assertOk()->assertDontSee('ui/screens.css', false);
        $this->actingAs($this->player)->get('/admin/clubs')->assertForbidden()->assertSee('No tienes permiso');
        foreach ([419, 500, 503] as $code) {
            $html = view('errors.'.$code)->render();
            $this->assertStringContainsString('Volver al mapa', $html);
        }
    }

    public function test_pagination_uses_the_custom_accessible_view(): void
    {
        foreach (range(1, 26) as $number) Club::create(['name' => 'Club prueba '.$number, 'lat' => 41.5, 'lng' => 2.1]);
        $this->actingAs($this->admin)->get('/admin/clubs')->assertOk()->assertSee('Paginación')->assertSee('Página 1 de 2');
    }
}
