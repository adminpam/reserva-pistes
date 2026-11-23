<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ManagerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $otherManager;
    private User $player;
    private Club $club;
    private Club $otherClub;
    private Court $court;
    private Court $otherCourt;
    private Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->otherManager = User::factory()->create(['role' => 'manager']);
        $this->player = User::factory()->create(['role' => 'player']);
        $this->sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $this->club = Club::create(['name' => 'Club Propio Ficticio', 'lat' => 41.5, 'lng' => 2.1, 'manager_id' => $this->manager->id]);
        $this->otherClub = Club::create(['name' => 'Club Ajeno Ficticio', 'lat' => 41.6, 'lng' => 2.2, 'manager_id' => $this->otherManager->id]);
        foreach (['court' => $this->club, 'otherCourt' => $this->otherClub] as $property => $club) {
            $this->$property = Court::create(['club_id' => $club->id, 'sport_id' => $this->sport->id,
                'name' => $property === 'court' ? 'Pista Propia Ficticia' : 'Pista Ajena Ficticia',
                'price_per_hour' => 1200, 'open_hour' => 8, 'close_hour' => 22]);
        }
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['club_id' => $this->club->id, 'sport_id' => $this->sport->id,
            'name' => 'Pista Editada Ficticia', 'price_euros' => '14.25', 'open_hour' => 8, 'close_hour' => 22], $changes);
    }

    private function booking(Court $court, string $start = '2026-11-25 10:00:00', string $end = '2026-11-25 11:00:00', string $status = 'confirmed'): Booking
    {
        return Booking::create(['court_id' => $court->id, 'user_id' => $this->player->id,
            'starts_at' => $start, 'ends_at' => $end, 'status' => $status, 'price_total' => 1200]);
    }

    public function test_manager_lists_only_own_clubs_courts_and_bookings(): void
    {
        $this->booking($this->court);
        $this->booking($this->otherCourt);
        $this->actingAs($this->manager)->get('/manager/courts')->assertOk()->assertSee($this->court->name)->assertDontSee($this->otherCourt->name)->assertDontSee($this->otherClub->name);
        $this->get('/manager/bookings')->assertOk()->assertSee($this->court->name)->assertDontSee($this->otherCourt->name);
    }

    public function test_manager_cannot_edit_delete_create_or_move_to_foreign_clubs(): void
    {
        $this->actingAs($this->manager);
        $this->get('/manager/courts/'.$this->otherCourt->id.'/edit')->assertForbidden();
        $this->patch('/manager/courts/'.$this->otherCourt->id, $this->payload())->assertForbidden();
        $this->delete('/manager/courts/'.$this->otherCourt->id)->assertForbidden();
        $this->post('/manager/courts', $this->payload(['club_id' => $this->otherClub->id]))->assertForbidden();
        $this->patch('/manager/courts/'.$this->court->id, $this->payload(['club_id' => $this->otherClub->id]))->assertForbidden();
        $this->assertDatabaseCount('courts', 2);
        $this->assertEquals($this->club->id, $this->court->fresh()->club_id);
        $this->assertSame('Pista Ajena Ficticia', $this->otherCourt->fresh()->name);
    }

    public function test_players_and_admins_cannot_use_manager_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([$this->player, $admin] as $actor) {
            $this->actingAs($actor);
            $this->get('/manager/courts')->assertForbidden();
            $this->get('/manager/courts/create')->assertForbidden();
            $this->get('/manager/courts/'.$this->court->id.'/edit')->assertForbidden();
            $this->get('/manager/bookings')->assertForbidden();
            $this->post('/manager/courts', $this->payload())->assertForbidden();
            $this->patch('/manager/courts/'.$this->court->id, $this->payload())->assertForbidden();
            $this->delete('/manager/courts/'.$this->court->id)->assertForbidden();
        }
        $this->assertDatabaseCount('courts', 2);
        $this->assertEquals(1200, $this->court->fresh()->price_per_hour);
    }

    public function test_guest_cannot_write_to_manager_routes(): void
    {
        $this->post('/manager/courts', $this->payload())->assertRedirect('/login');
        $this->patch('/manager/courts/'.$this->court->id, $this->payload())->assertRedirect('/login');
        $this->delete('/manager/courts/'.$this->court->id)->assertRedirect('/login');
        $this->assertDatabaseCount('courts', 2);
    }

    public function test_manager_creates_court_with_server_converted_price_and_checkbox(): void
    {
        $this->actingAs($this->manager)->post('/manager/courts', $this->payload([
            'name' => 'Pista Nueva Ficticia', 'is_outdoor' => '1', 'price_per_hour' => 1,
        ]))->assertRedirect(route('manager.courts.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courts', ['name' => 'Pista Nueva Ficticia', 'club_id' => $this->club->id,
            'sport_id' => $this->sport->id, 'price_per_hour' => 1425, 'is_outdoor' => true]);
    }

    public function test_price_and_hours_reject_invalid_values_without_partial_update(): void
    {
        $this->actingAs($this->manager);
        foreach ([['price_euros' => '10.001'], ['price_euros' => -1], ['price_euros' => 501],
            ['open_hour' => 22, 'close_hour' => 22], ['close_hour' => 25], ['sport_id' => 999999], ['club_id' => [1]]] as $invalid) {
            $this->patchJson('/manager/courts/'.$this->court->id, $this->payload($invalid))->assertUnprocessable();
        }
        $this->assertDatabaseHas('courts', ['id' => $this->court->id, 'name' => 'Pista Propia Ficticia', 'price_per_hour' => 1200, 'open_hour' => 8, 'close_hour' => 22]);
    }

    public function test_price_change_keeps_booking_snapshot_and_unchecked_outdoor_becomes_false(): void
    {
        $booking = $this->booking($this->court);
        $this->court->update(['is_outdoor' => true]);
        $this->actingAs($this->manager)->patch('/manager/courts/'.$this->court->id, $this->payload())->assertSessionHasNoErrors();
        $this->assertEquals(1425, $this->court->fresh()->price_per_hour);
        $this->assertFalse($this->court->fresh()->is_outdoor);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'price_total' => 1200]);
    }

    public function test_booked_court_keeps_club_and_sport_even_for_cancelled_history(): void
    {
        $booking = $this->booking($this->court, status: 'cancelled');
        $sameManagerClub = Club::create(['name' => 'Segundo Club Propio Ficticio', 'lat' => 41.55, 'lng' => 2.15, 'manager_id' => $this->manager->id]);
        $otherSport = Sport::create(['name' => 'Bádminton', 'slug' => 'badminton', 'players_per_match' => 4]);
        $this->actingAs($this->manager)->patchJson('/manager/courts/'.$this->court->id, $this->payload(['club_id' => $sameManagerClub->id]))->assertUnprocessable()->assertJsonValidationErrors('club_id');
        $this->patchJson('/manager/courts/'.$this->court->id, $this->payload(['sport_id' => $otherSport->id]))->assertUnprocessable()->assertJsonValidationErrors('sport_id');
        $this->assertDatabaseHas('courts', ['id' => $this->court->id, 'club_id' => $this->club->id, 'sport_id' => $this->sport->id, 'price_per_hour' => 1200]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_unbooked_court_can_change_club_and_sport_within_manager_scope(): void
    {
        $nextClub = Club::create(['name' => 'Club de Destino Ficticio', 'lat' => 41.55, 'lng' => 2.15, 'manager_id' => $this->manager->id]);
        $nextSport = Sport::create(['name' => 'Bádminton', 'slug' => 'badminton', 'players_per_match' => 4]);
        $this->actingAs($this->manager)->patch('/manager/courts/'.$this->court->id, $this->payload(['club_id' => $nextClub->id, 'sport_id' => $nextSport->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courts', ['id' => $this->court->id, 'club_id' => $nextClub->id, 'sport_id' => $nextSport->id]);
    }

    public function test_future_booking_cannot_be_left_outside_new_hours(): void
    {
        $this->booking($this->court, '2026-11-25 10:30:00', '2026-11-25 11:30:00');
        $this->actingAs($this->manager);
        foreach ([['open_hour' => 11], ['close_hour' => 11]] as $change) {
            $this->patchJson('/manager/courts/'.$this->court->id, $this->payload($change))->assertUnprocessable()->assertJsonValidationErrors('open_hour');
        }
        $this->assertDatabaseHas('courts', ['id' => $this->court->id, 'open_hour' => 8, 'close_hour' => 22, 'price_per_hour' => 1200]);
        $this->deleteJson('/manager/courts/'.$this->court->id)->assertUnprocessable()->assertJsonValidationErrors('court');
        $this->assertDatabaseCount('bookings', 1);
        $this->patch('/manager/courts/'.$this->court->id, $this->payload(['open_hour' => 7, 'close_hour' => 23]))->assertSessionHasNoErrors();
    }

    public function test_ongoing_booking_is_protected_even_when_it_started_before_now(): void
    {
        $this->booking($this->court, '2026-11-24 11:30:00', '2026-11-24 12:30:00');
        $this->actingAs($this->manager)->patchJson('/manager/courts/'.$this->court->id, $this->payload(['open_hour' => 12]))->assertUnprocessable()->assertJsonValidationErrors('open_hour');
        $this->assertEquals(8, $this->court->fresh()->open_hour);
    }

    public function test_cancelled_and_finished_bookings_do_not_prevent_new_hours_but_prevent_deletion(): void
    {
        $this->booking($this->court, '2026-11-25 08:00:00', '2026-11-25 09:00:00', 'cancelled');
        $this->booking($this->court, '2026-11-20 08:00:00', '2026-11-20 09:00:00');
        $this->actingAs($this->manager)->patch('/manager/courts/'.$this->court->id, $this->payload(['open_hour' => 12, 'close_hour' => 20]))->assertSessionHasNoErrors();
        $this->deleteJson('/manager/courts/'.$this->court->id)->assertUnprocessable()->assertJsonValidationErrors('court');
        $this->assertDatabaseCount('bookings', 2);
        $this->assertDatabaseHas('courts', ['id' => $this->court->id, 'open_hour' => 12, 'close_hour' => 20]);
    }

    public function test_closing_at_midnight_keeps_last_hour_and_opening_later_is_rejected(): void
    {
        $this->court->update(['close_hour' => 24]);
        $this->booking($this->court, '2026-11-25 23:00:00', '2026-11-26 00:00:00');
        $this->actingAs($this->manager)->patch('/manager/courts/'.$this->court->id, $this->payload(['open_hour' => 7, 'close_hour' => 24]))->assertSessionHasNoErrors();
        $this->patchJson('/manager/courts/'.$this->court->id, $this->payload(['close_hour' => 23]))->assertUnprocessable()->assertJsonValidationErrors('open_hour');
        $this->assertEquals(24, $this->court->fresh()->close_hour);
    }
}
