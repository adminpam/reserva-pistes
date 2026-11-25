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

// Peticiones reales a las rutas del panel; todos los datos son ficticios.
class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $player;
    private Club $club;
    private Court $court;
    private Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->player = User::factory()->create(['role' => 'player']);
        $this->sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $this->club = Club::create(['name' => 'Club Administración Ficticio', 'lat' => 41.5, 'lng' => 2.1, 'manager_id' => $this->manager->id]);
        $this->court = Court::create(['club_id' => $this->club->id, 'sport_id' => $this->sport->id, 'name' => 'Pista Administración Ficticia', 'price_per_hour' => 1200, 'open_hour' => 8, 'close_hour' => 22]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function booking(User $owner, string $status = 'confirmed', bool $open = false, string $day = '2026-11-25'): Booking
    {
        return Booking::create(['court_id' => $this->court->id, 'user_id' => $owner->id,
            'starts_at' => $day.' 10:00:00', 'ends_at' => $day.' 11:00:00',
            'price_total' => 1200, 'status' => $status, 'is_open' => $open]);
    }

    public function test_players_and_managers_cannot_write_to_admin_routes(): void
    {
        foreach ([$this->player, $this->manager] as $actor) {
            $this->actingAs($actor);
            $this->patch('/admin/clubs/'.$this->club->id, ['manager_id' => null])->assertForbidden();
            $this->delete('/admin/clubs/'.$this->club->id)->assertForbidden();
            $this->post('/admin/sports', ['name' => 'Deporte ajeno', 'players_per_match' => 4])->assertForbidden();
            $this->patch('/admin/sports/'.$this->sport->id, ['players_per_match' => 4])->assertForbidden();
            $this->delete('/admin/sports/'.$this->sport->id)->assertForbidden();
            $this->patch('/admin/users/'.$this->player->id, ['role' => 'admin'])->assertForbidden();
            $this->delete('/admin/users/'.$this->admin->id)->assertForbidden();
        }
        $this->assertDatabaseCount('sports', 2);
        $this->assertDatabaseCount('users', 3);
        $this->assertEquals($this->manager->id, $this->club->fresh()->manager_id);
        $this->assertSame('player', $this->player->fresh()->role);
    }

    public function test_guests_cannot_write_to_admin_routes(): void
    {
        foreach ([['PATCH', '/admin/clubs/'.$this->club->id], ['DELETE', '/admin/clubs/'.$this->club->id],
            ['POST', '/admin/sports'], ['PATCH', '/admin/sports/'.$this->sport->id], ['DELETE', '/admin/sports/'.$this->sport->id],
            ['PATCH', '/admin/users/'.$this->player->id], ['DELETE', '/admin/users/'.$this->player->id]] as [$method, $url]) {
            $this->call($method, $url)->assertRedirect('/login');
        }
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('courts', 1);
    }

    public function test_only_a_manager_can_be_assigned_and_missing_field_is_rejected(): void
    {
        $this->actingAs($this->admin);
        foreach ([$this->player->id, $this->admin->id, 999999, ['invalid']] as $id) {
            $this->patchJson('/admin/clubs/'.$this->club->id, ['manager_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('manager_id');
        }
        $this->patchJson('/admin/clubs/'.$this->club->id, [])->assertUnprocessable()->assertJsonValidationErrors('manager_id');
        $this->assertEquals($this->manager->id, $this->club->fresh()->manager_id);
    }

    public function test_reassignment_transfers_access_and_unassignment_is_explicit(): void
    {
        $next = User::factory()->create(['role' => 'manager']);
        $this->actingAs($this->admin)->patch('/admin/clubs/'.$this->club->id, ['manager_id' => $next->id])->assertSessionHasNoErrors();
        $this->actingAs($this->manager)->get('/manager/courts/'.$this->court->id.'/edit')->assertForbidden();
        $this->actingAs($next)->get('/manager/courts/'.$this->court->id.'/edit')->assertOk();
        $this->actingAs($this->admin)->patch('/admin/clubs/'.$this->club->id, ['manager_id' => ''])->assertSessionHasNoErrors();
        $this->assertNull($this->club->fresh()->manager_id);
        $this->actingAs($next)->get('/manager/courts/'.$this->court->id.'/edit')->assertForbidden();
    }

    public function test_manager_must_relinquish_clubs_before_changing_role_or_deleting(): void
    {
        $this->actingAs($this->admin);
        foreach (['player', 'admin'] as $role) {
            $this->patchJson('/admin/users/'.$this->manager->id, ['role' => $role])->assertUnprocessable()->assertJsonValidationErrors('role');
        }
        $this->deleteJson('/admin/users/'.$this->manager->id)->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->assertSame('manager', $this->manager->fresh()->role);
        $this->patch('/admin/clubs/'.$this->club->id, ['manager_id' => null])->assertSessionHasNoErrors();
        $this->patch('/admin/users/'.$this->manager->id, ['role' => 'player'])->assertSessionHasNoErrors();
        $this->assertSame('player', $this->manager->fresh()->role);
    }

    public function test_admin_can_change_other_roles_but_not_demote_or_delete_self(): void
    {
        $this->actingAs($this->admin);
        $this->patch('/admin/users/'.$this->player->id, ['role' => 'manager'])->assertSessionHasNoErrors();
        $this->assertSame('manager', $this->player->fresh()->role);
        $this->patchJson('/admin/users/'.$this->player->id, ['role' => 'superadmin'])->assertUnprocessable();
        $this->patchJson('/admin/users/'.$this->admin->id, ['role' => 'player'])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->deleteJson('/admin/users/'.$this->admin->id)->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_booking_owners_are_kept_for_confirmed_cancelled_and_past_history(): void
    {
        $this->actingAs($this->admin);
        foreach ([['confirmed', '2026-11-25'], ['cancelled', '2026-11-25'], ['confirmed', '2026-11-20']] as [$status, $day]) {
            $owner = User::factory()->create();
            $booking = $this->booking($owner, $status, false, $day);
            $this->deleteJson('/admin/users/'.$owner->id)->assertUnprocessable()->assertJsonValidationErrors('user');
            $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'user_id' => $owner->id, 'price_total' => 1200]);
            $this->assertDatabaseHas('users', ['id' => $owner->id]);
        }
    }

    public function test_participants_are_kept_even_when_the_match_is_cancelled(): void
    {
        $match = $this->booking($this->manager, 'cancelled', true);
        $match->players()->attach($this->player->id);
        $this->actingAs($this->admin)->deleteJson('/admin/users/'.$this->player->id)->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->assertDatabaseHas('booking_players', ['booking_id' => $match->id, 'user_id' => $this->player->id]);
        $this->assertDatabaseHas('bookings', ['id' => $match->id, 'status' => 'cancelled']);
    }

    public function test_unused_user_can_be_deleted(): void
    {
        $this->actingAs($this->admin)->delete('/admin/users/'.$this->player->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $this->player->id]);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_club_requires_explicit_court_removal_and_booked_courts_survive(): void
    {
        $this->actingAs($this->admin)->deleteJson('/admin/clubs/'.$this->club->id)->assertUnprocessable()->assertJsonValidationErrors('club');
        $this->assertDatabaseHas('courts', ['id' => $this->court->id]);
        $booking = $this->booking($this->player, 'cancelled');
        $this->deleteJson('/admin/clubs/'.$this->club->id)->assertUnprocessable();
        $this->actingAs($this->manager)->deleteJson('/manager/courts/'.$this->court->id)->assertUnprocessable()->assertJsonValidationErrors('court');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('clubs', ['id' => $this->club->id]);
    }

    public function test_unused_court_then_empty_club_can_be_removed(): void
    {
        $this->actingAs($this->manager)->delete('/manager/courts/'.$this->court->id)->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->delete('/admin/clubs/'.$this->club->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('courts', ['id' => $this->court->id]);
        $this->assertDatabaseMissing('clubs', ['id' => $this->club->id]);
        $this->assertDatabaseHas('sports', ['id' => $this->sport->id]);
    }

    public function test_sport_creation_handles_equivalent_names_and_empty_slugs(): void
    {
        $this->actingAs($this->admin);
        foreach (['Tenis', 'Ténis!', '!!!'] as $name) {
            $this->postJson('/admin/sports', ['name' => $name, 'players_per_match' => 4])->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertDatabaseCount('sports', 2);
        $this->post('/admin/sports', ['name' => 'Deporte de prueba', 'players_per_match' => 6])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sports', ['slug' => 'deporte-de-prueba', 'players_per_match' => 6]);
        foreach ([1, 31, 'texto'] as $capacity) {
            $this->postJson('/admin/sports', ['name' => 'Deporte inválido', 'players_per_match' => $capacity])->assertUnprocessable()->assertJsonValidationErrors('players_per_match');
        }
    }

    public function test_capacity_changes_before_open_matches_and_ignores_private_bookings(): void
    {
        $this->booking($this->player);
        $this->actingAs($this->admin)->patch('/admin/sports/'.$this->sport->id, ['players_per_match' => 4])->assertSessionHasNoErrors();
        $this->assertEquals(4, $this->sport->fresh()->players_per_match);
    }

    public function test_any_open_match_preserves_capacity_including_past_and_cancelled(): void
    {
        $this->actingAs($this->admin);
        foreach ([['confirmed', '2026-11-25'], ['cancelled', '2026-11-25'], ['confirmed', '2026-11-20']] as [$status, $day]) {
            $match = $this->booking($this->player, $status, true, $day);
            $match->players()->attach($this->player->id);
            $this->patchJson('/admin/sports/'.$this->sport->id, ['players_per_match' => 6])->assertUnprocessable()->assertJsonValidationErrors('players_per_match');
            $this->assertEquals(2, $this->sport->fresh()->players_per_match);
            $this->assertDatabaseHas('booking_players', ['booking_id' => $match->id, 'user_id' => $this->player->id]);
            $this->patch('/admin/sports/'.$this->sport->id, ['players_per_match' => 2])->assertSessionHasNoErrors();
            // Retiramos solo la fixture para que el siguiente estado se pruebe aislado.
            $match->delete();
        }
    }

    public function test_sport_with_courts_or_profile_levels_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)->deleteJson('/admin/sports/'.$this->sport->id)->assertUnprocessable()->assertJsonValidationErrors('sport');
        $other = Sport::create(['name' => 'Bádminton', 'slug' => 'badminton', 'players_per_match' => 4]);
        UserSportLevel::create(['user_id' => $this->player->id, 'sport_id' => $other->id, 'level' => 3]);
        $this->deleteJson('/admin/sports/'.$other->id)->assertUnprocessable()->assertJsonValidationErrors('sport');
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $other->id, 'level' => 3]);
        $this->assertDatabaseCount('sports', 3);
    }

    public function test_unused_sport_can_be_deleted(): void
    {
        $unused = Sport::create(['name' => 'Deporte temporal', 'slug' => 'temporal', 'players_per_match' => 2]);
        $this->actingAs($this->admin)->delete('/admin/sports/'.$unused->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sports', ['id' => $unused->id]);
        $this->assertDatabaseHas('sports', ['id' => $this->sport->id]);
    }

    public function test_admin_search_filters_records_and_rejects_array_input(): void
    {
        Club::create(['name' => 'Club Oculto Ficticio', 'lat' => 41.6, 'lng' => 2.2]);
        $target = User::factory()->create(['name' => 'Usuario Visible Ficticio', 'email' => 'visible-admin@example.test']);
        $this->actingAs($this->admin)->get('/admin/clubs?q=Administraci%C3%B3n')->assertOk()->assertSee($this->club->name)->assertDontSee('Club Oculto Ficticio');
        $this->get('/admin/users?q=visible-admin')->assertOk()->assertSee($target->name)->assertDontSee($this->player->email);
        foreach (['/admin/clubs', '/admin/users'] as $url) {
            $this->getJson($url.'?q[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('q');
        }
    }
}
