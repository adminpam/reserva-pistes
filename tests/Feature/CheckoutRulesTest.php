<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\User;
use App\Services\ClubAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesDemoCheckout;
use Tests\TestCase;

class CheckoutRulesTest extends TestCase
{
    use RefreshDatabase, CreatesDemoCheckout;

    public function test_begin_holds_the_hour_without_creating_a_booking_or_players(): void
    {
        $other = User::factory()->create();
        $checkout = $this->begin(['user_id' => $other->id, 'amount_cents' => 1, 'status' => 'paid', 'expires_at' => '2099-01-01']);
        $this->assertSame($this->player->id, $checkout->user_id);
        $this->assertSame(1450, $checkout->amount_cents);
        $this->assertSame('pending', $checkout->status);
        $this->assertTrue($checkout->expires_at->eq(now()->addMinutes(5)));
        $this->assertEquals(60, $checkout->starts_at->diffInMinutes($checkout->ends_at));
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_players', 0);
        $this->assertDatabaseCount('demo_payments', 0);
        $this->assertTrue(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
        $snapshot = app(ClubAvailability::class)->forDate($this->court->club, '2026-11-25');
        $hour = collect($snapshot['courts'][0]['hours'])->firstWhere('hour', 10);
        $this->assertFalse($hour['available']);
        $this->assertStringNotContainsString($checkout->id, json_encode($snapshot));
    }

    public function test_repeating_a_key_keeps_the_quote_and_expiry_even_after_payment(): void
    {
        $checkout = $this->begin();
        $this->travel(1)->minutes();
        $this->court->update(['price_per_hour' => 9900]);
        $same = $this->begin(['checkout_key' => $checkout->id]);
        $this->assertTrue($same->expires_at->eq($checkout->expires_at));
        $this->assertSame(1450, $same->amount_cents);
        $this->approve($same);
        $this->travel(2)->days();
        $this->begin(['checkout_key' => $checkout->id]);
        $this->assertDatabaseCount('booking_checkouts', 1);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('demo_payments', 1);
    }

    public function test_a_new_key_for_the_same_active_selection_resumes_without_renewing(): void
    {
        $checkout = $this->begin();
        $this->travel(1)->minutes();
        $this->post('/bookings', $this->selection())->assertRedirect(route('checkout.show', $checkout));
        $this->assertDatabaseCount('booking_checkouts', 1);
        $this->assertTrue($checkout->fresh()->expires_at->eq($checkout->expires_at));
    }

    public function test_a_key_cannot_be_reused_for_another_selection_or_user(): void
    {
        $checkout = $this->begin();
        $this->postJson('/bookings', $this->selection(['checkout_key' => $checkout->id, 'hour' => 11]))
            ->assertUnprocessable()->assertJsonValidationErrors('checkout_key');
        $this->actingAs(User::factory()->create())->post('/bookings', $this->selection(['checkout_key' => $checkout->id]))->assertForbidden();
        $this->assertDatabaseCount('booking_checkouts', 1);
    }

    public function test_other_users_including_admin_cannot_access_or_modify_checkout(): void
    {
        $checkout = $this->begin();
        foreach (['player', 'admin', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('checkout.show', $checkout))->assertForbidden();
            $this->post(route('checkout.pay', $checkout), $this->card())->assertForbidden();
            $this->delete(route('checkout.cancel', $checkout))->assertForbidden();
            $this->post(route('checkout.retry', $checkout), ['checkout_key' => (string) Str::uuid()])->assertForbidden();
            $this->get(route('checkout.index'))->assertOk()->assertDontSee('Pista demo');
        }
        $this->assertSame('pending', $checkout->fresh()->status);
    }

    public function test_active_hold_blocks_another_player_and_expiry_frees_it_without_cron(): void
    {
        $checkout = $this->begin();
        $other = User::factory()->create();
        $this->actingAs($other)->postJson('/bookings', $this->selection())->assertUnprocessable()->assertJsonValidationErrors('hour');
        $this->travel(5)->minutes();
        $this->assertSame('pending', $checkout->fresh()->status); // Todavía sin leer su pantalla.
        $this->assertFalse(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
        $this->post('/bookings', $this->selection())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('booking_checkouts', 2);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_hold_cannot_run_beyond_slot_start_and_late_payment_cannot_confirm(): void
    {
        $this->travelTo(now()->setTime(12, 58));
        $checkout = $this->begin(['date' => '2026-11-24', 'hour' => 13]);
        $this->assertTrue($checkout->expires_at->eq($checkout->starts_at));
        $this->travel(2)->minutes();
        $this->approve($checkout);
        $this->assertSame('expired', $checkout->fresh()->status);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('demo_payments', 0);
    }

    public function test_cancel_pending_is_repeatable_and_late_pay_does_not_revive_it(): void
    {
        $checkout = $this->begin();
        $this->delete(route('checkout.cancel', $checkout))->assertRedirect();
        $this->delete(route('checkout.cancel', $checkout))->assertRedirect();
        $this->post(route('checkout.pay', $checkout), [])->assertRedirect();
        $this->assertSame('cancelled', $checkout->fresh()->status);
        $this->assertFalse(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('demo_payments', 0);
    }

    public function test_three_active_holds_limit_does_not_count_expired_or_cancelled(): void
    {
        $first = $this->begin();
        $this->begin(['hour' => 11]);
        $this->begin(['hour' => 12]);
        $this->postJson('/bookings', $this->selection(['hour' => 13]))->assertUnprocessable()->assertJsonValidationErrors('hour');
        $this->delete(route('checkout.cancel', $first));
        $this->begin(['hour' => 13]);
        $this->travel(5)->minutes();
        $this->begin(['hour' => 14]);
        $this->assertSame(1, $this->player->checkouts()->active()->count());
    }

    public function test_creation_attempts_are_limited_per_user_even_with_new_keys(): void
    {
        $this->actingAs($this->player);
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/bookings', $this->selection(['hour' => 7]))->assertUnprocessable();
        }
        $this->postJson('/bookings', $this->selection())->assertUnprocessable()->assertJsonValidationErrors('hour');
        $this->assertDatabaseCount('booking_checkouts', 0);
        $this->travel(61)->seconds();
        $this->begin();
    }

    public function test_pending_lists_are_private_and_not_published_as_matches(): void
    {
        $this->begin(['is_open' => 1]);
        $this->get('/my/matches')->assertOk()->assertSee('Pendientes de pago de prueba')->assertDontSee('Aún no tienes partidos');
        $this->get('/matches')->assertOk()->assertSee('No hay partidos con esos filtros');
        $this->begin(['hour' => 11]);
        $this->get('/my/bookings')->assertOk()->assertSee('Pendientes de pago de prueba')->assertDontSee('Todavía no tienes reservas');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_missing_key_and_guest_cannot_start_checkout(): void
    {
        $data = $this->selection();
        unset($data['checkout_key']);
        $this->actingAs($this->player)->postJson('/bookings', $data)->assertUnprocessable()->assertJsonValidationErrors('checkout_key');
        $this->app['auth']->forgetGuards();
        $this->post('/bookings', $this->selection())->assertRedirect('/login');
        $this->assertDatabaseCount('booking_checkouts', 0);
    }

    public function test_retry_uses_current_quote_and_preserves_previous_attempt(): void
    {
        $checkout = $this->begin();
        $this->delete(route('checkout.cancel', $checkout));
        $this->court->update(['price_per_hour' => 2000]);
        $id = (string) Str::uuid();
        $this->post(route('checkout.retry', $checkout), ['checkout_key' => $id, 'amount_cents' => 1])
            ->assertRedirect(route('checkout.show', $id))->assertSessionHasNoErrors();
        $this->assertSame(2000, BookingCheckout::findOrFail($id)->amount_cents);
        $this->assertSame(1450, $checkout->fresh()->amount_cents);
        $this->assertSame('cancelled', $checkout->fresh()->status);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_checkout_history_protects_court_and_user_even_without_a_booking(): void
    {
        $checkout = $this->begin();
        $this->delete(route('checkout.cancel', $checkout));
        $manager = User::factory()->create(['role' => 'manager']);
        $this->court->club->update(['manager_id' => $manager->id]);
        $this->actingAs($manager)->delete('/manager/courts/'.$this->court->id)->assertSessionHasErrors('court');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete('/admin/users/'.$this->player->id)->assertSessionHasErrors('user');
        $this->assertDatabaseHas('courts', ['id' => $this->court->id]);
        $this->assertDatabaseHas('users', ['id' => $this->player->id]);
    }

    public function test_manager_hours_cannot_exclude_an_active_hold_but_can_after_expiry(): void
    {
        $this->begin();
        $manager = User::factory()->create(['role' => 'manager']);
        $this->court->club->update(['manager_id' => $manager->id]);
        $payload = ['club_id' => $this->court->club_id, 'sport_id' => $this->court->sport_id,
            'name' => 'Pista demo', 'price_euros' => '14.50', 'open_hour' => 11, 'close_hour' => 22];
        $this->actingAs($manager)->patchJson('/manager/courts/'.$this->court->id, $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('open_hour');
        $this->travel(5)->minutes();
        $this->patch('/manager/courts/'.$this->court->id, $payload)->assertSessionHasNoErrors();
        $this->assertEquals(11, $this->court->fresh()->open_hour);
    }
}
