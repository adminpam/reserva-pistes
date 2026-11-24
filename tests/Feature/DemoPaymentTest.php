<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\DemoPayment;
use App\Models\User;
use App\Models\UserSportLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CreatesDemoCheckout;
use Tests\TestCase;

class DemoPaymentTest extends TestCase
{
    use RefreshDatabase, CreatesDemoCheckout;

    public function test_approved_payment_creates_one_booking_and_an_immutable_quote_receipt(): void
    {
        $checkout = $this->begin();
        $this->court->update(['price_per_hour' => 3000]);
        $payload = $this->card();
        $payload += ['status' => 'declined', 'amount_cents' => 1, 'booking_id' => 999];
        $this->post(route('checkout.pay', $checkout), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $booking = Booking::sole();
        $payment = DemoPayment::sole();
        $this->assertSame('confirmed', $booking->status);
        $this->assertEquals(1450, $booking->price_total);
        $this->assertSame(1450, $payment->amount_cents);
        $this->assertSame('approved', $payment->status);
        $this->assertSame($booking->id, $payment->booking_id);
        $this->assertSame('DEMO-'.strtoupper(str_replace('-', '', $checkout->id)), $payment->reference);
        $this->assertSame('paid', $checkout->fresh()->status);
        $this->get(route('checkout.show', $checkout))->assertOk()->assertSee($payment->reference)->assertSee('14,50');
        $this->get('/my/bookings')->assertOk()->assertSee('Justificante de prueba');
        $this->assertTrue(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
    }

    public function test_replayed_or_empty_payment_post_cannot_duplicate_records(): void
    {
        $checkout = $this->approve($this->begin());
        $processed = DemoPayment::sole()->processed_at;
        $this->travel(1)->minutes();
        $this->post(route('checkout.pay', $checkout), [])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('checkout.pay', $checkout), $this->card('4000000000000002'))->assertRedirect();
        $this->assertDatabaseCount('demo_payments', 1);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame('approved', DemoPayment::sole()->status);
        $this->assertTrue(DemoPayment::sole()->processed_at->eq($processed));
    }

    public function test_decline_releases_hour_and_does_not_create_a_reservation(): void
    {
        $checkout = $this->begin();
        $this->post(route('checkout.pay', $checkout), $this->card('4000000000000002'))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('declined', $checkout->fresh()->status);
        $this->assertSame('declined', DemoPayment::sole()->status);
        $this->assertNull(DemoPayment::sole()->booking_id);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertFalse(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
        $this->post(route('checkout.pay', $checkout), $this->card())->assertRedirect();
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('demo_payments', 1);
        $this->get(route('checkout.show', $checkout))->assertOk()->assertSee('Pago de prueba rechazado');
    }

    public function test_retry_after_decline_quotes_current_price_before_new_approval(): void
    {
        $checkout = $this->begin();
        $this->post(route('checkout.pay', $checkout), $this->card('4000000000000002'));
        $this->court->update(['price_per_hour' => 1800]);
        $key = (string) Str::uuid();
        $this->post(route('checkout.retry', $checkout), ['checkout_key' => $key])->assertSessionHasNoErrors()->assertRedirect(route('checkout.show', $key));
        $new = BookingCheckout::findOrFail($key);
        $this->assertSame(1800, $new->amount_cents);
        $this->assertSame('pending', $new->status);
        $this->assertDatabaseCount('bookings', 0);
        $this->approve($new);
        $this->assertDatabaseCount('demo_payments', 2);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals(1800, Booking::sole()->price_total);
    }

    public function test_fake_card_validation_leaves_hold_pending_and_never_flashes_card_input(): void
    {
        $checkout = $this->begin();
        foreach (['holder' => 'Titular incorrecto', 'number' => '1111111111111111', 'expiry' => '01/20', 'cvc' => '999'] as $field => $value) {
            $data = $this->card();
            $data['payment'][$field] = $value;
            $this->postJson(route('checkout.pay', $checkout), $data)->assertUnprocessable()->assertJsonValidationErrors('payment.'.$field);
        }
        $data = $this->card('1111111111111111');
        $this->post(route('checkout.pay', $checkout), $data)->assertSessionHasErrors('payment.number')->assertSessionMissing('_old_input.payment');
        $this->assertSame('pending', $checkout->fresh()->status);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('demo_payments', 0);
        $this->get(route('checkout.show', $checkout))->assertOk()->assertDontSee('1111111111111111');
    }

    public function test_malformed_payment_arrays_are_validation_errors_not_server_errors(): void
    {
        $checkout = $this->begin();
        foreach ([null, 'invalid', ['number' => ['nested']], array_merge($this->card()['payment'], ['unexpected' => 'x'])] as $input) {
            $this->postJson(route('checkout.pay', $checkout), ['payment' => $input])->assertUnprocessable();
        }
        $this->assertSame('pending', $checkout->fresh()->status);
        $this->assertDatabaseCount('demo_payments', 0);
    }

    public function test_card_data_is_not_sent_to_database_or_kept_as_old_input(): void
    {
        $checkout = $this->begin();
        DB::enableQueryLog();
        $this->approve($checkout);
        $queries = json_encode(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertStringNotContainsString('4242424242424242', $queries);
        $this->assertStringNotContainsString('Persona Demo', $queries);
        $this->assertStringNotContainsString('payment[number]', $queries);
        $this->assertArrayNotHasKey('number', DemoPayment::sole()->getAttributes());
        $this->assertArrayNotHasKey('holder', BookingCheckout::sole()->getAttributes());
        $this->assertNull(session()->get('_old_input.payment'));
    }

    public function test_open_match_only_appears_after_approval_with_organizer_once(): void
    {
        $checkout = $this->begin(['is_open' => 1]);
        $this->get('/matches')->assertOk()->assertSee('No hay partidos con esos filtros');
        $this->approve($checkout);
        $booking = Booking::sole();
        $this->assertSame(1, $booking->players()->count());
        $this->post(route('checkout.pay', $checkout), $this->card());
        $this->assertSame(1, $booking->players()->count());
        $this->get('/my/matches')->assertOk()->assertSee('Justificante de prueba');
        $this->actingAs(User::factory()->create())->post(route('matches.join', $booking))->assertSessionHasNoErrors();
        $this->assertSame(2, $booking->players()->count());
        $this->assertDatabaseCount('demo_payments', 1);
        $this->get('/my/matches')->assertOk()->assertDontSee('Justificante de prueba');
    }

    public function test_conflicting_booking_prevents_payment_and_records_a_terminal_conflict(): void
    {
        $checkout = $this->begin();
        Booking::create(['court_id' => $this->court->id, 'user_id' => $this->player->id,
            'starts_at' => '2026-11-25 10:30:00', 'ends_at' => '2026-11-25 11:30:00', 'price_total' => 1000, 'status' => 'confirmed']);
        $this->post(route('checkout.pay', $checkout), $this->card())->assertRedirect();
        $this->assertSame('conflict', $checkout->fresh()->status);
        $this->assertSame('slot_unavailable', $checkout->fresh()->failure_reason);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('demo_payments', 0);
        $this->postJson(route('checkout.retry', $checkout), ['checkout_key' => (string) Str::uuid()])
            ->assertUnprocessable()->assertJsonValidationErrors('hour');
    }

    public function test_level_is_checked_again_before_approving_a_restricted_match(): void
    {
        $level = UserSportLevel::create(['user_id' => $this->player->id, 'sport_id' => $this->court->sport_id, 'level' => 3]);
        $checkout = $this->begin(['is_open' => 1, 'min_level' => 2, 'max_level' => 4]);
        $level->update(['level' => 5]);
        $this->post(route('checkout.pay', $checkout), $this->card())->assertRedirect();
        $this->assertSame('level_changed', $checkout->fresh()->failure_reason);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('demo_payments', 0);
    }

    public function test_hours_changed_outside_the_application_are_rechecked_before_pay(): void
    {
        $checkout = $this->begin();
        $this->court->update(['open_hour' => 11]);
        $this->post(route('checkout.pay', $checkout), $this->card())->assertRedirect();
        $this->assertSame('hours_changed', $checkout->fresh()->failure_reason);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_private_cancellation_reverts_simulation_once_and_keeps_reference(): void
    {
        $checkout = $this->approve($this->begin());
        $booking = Booking::sole();
        $payment = DemoPayment::sole();
        $this->patch(route('bookings.cancel', $booking))->assertSessionHasNoErrors();
        $refundedAt = $payment->fresh()->refunded_at;
        $this->travel(1)->minutes();
        $this->patch(route('bookings.cancel', $booking))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->refunded_at->eq($refundedAt));
        $this->assertSame($payment->reference, $checkout->fresh()->payment->reference);
        $this->get(route('checkout.show', $checkout))->assertOk()->assertSee('Simulación revertida');
        $this->assertFalse(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
        $this->assertDatabaseCount('demo_payments', 1);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_organizer_cancellation_reverts_payment_and_keeps_participants(): void
    {
        $checkout = $this->approve($this->begin(['is_open' => 1]));
        $booking = Booking::sole();
        $other = User::factory()->create();
        $this->actingAs($other)->post(route('matches.join', $booking))->assertSessionHasNoErrors();
        $this->actingAs($this->player)->delete(route('matches.leave', $booking))->assertSessionHasNoErrors();
        $this->assertSame('refunded', DemoPayment::sole()->status);
        $this->assertSame(2, $booking->players()->count());
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertFalse(Booking::overlaps($this->court->id, $checkout->starts_at, $checkout->ends_at));
    }

    public function test_stale_cancel_or_retry_after_payment_cannot_cancel_confirmed_booking(): void
    {
        $checkout = $this->approve($this->begin());
        $this->delete(route('checkout.cancel', $checkout))->assertRedirect();
        $this->post(route('checkout.retry', $checkout), [])->assertRedirect(route('checkout.show', $checkout));
        $this->assertSame('confirmed', Booking::sole()->status);
        $this->assertSame('approved', DemoPayment::sole()->status);
        $this->assertDatabaseCount('booking_checkouts', 1);
    }

    public function test_payment_history_and_receipt_have_demo_notice_and_pagination(): void
    {
        $checkout = $this->approve($this->begin());
        $this->get(route('checkout.index'))->assertOk()->assertSee('no se realiza ningún cobro real')->assertSee('Ver detalle');
        $this->get(route('checkout.show', $checkout))->assertOk()->assertSee('No uses datos personales ni tarjetas reales');
        $this->assertDatabaseCount('demo_payments', 1);
        foreach (range(1, 15) as $index) {
            BookingCheckout::create(['id' => (string) Str::uuid(), 'user_id' => $this->player->id,
                'court_id' => $this->court->id, 'starts_at' => $checkout->starts_at, 'ends_at' => $checkout->ends_at,
                'expires_at' => now(), 'amount_cents' => 1450, 'status' => 'cancelled']);
        }
        $this->get(route('checkout.index'))->assertOk()->assertSee('Paginación')->assertSee('Página 1 de 2');
        $this->get(route('checkout.index', ['page' => 2]))->assertOk()->assertSee('Página 2 de 2');
    }

    public function test_declined_open_match_does_not_publish_or_attach_organizer(): void
    {
        $checkout = $this->begin(['is_open' => 1]);
        $this->post(route('checkout.pay', $checkout), $this->card('4000000000000002'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_players', 0);
        $this->get('/matches')->assertOk()->assertSee('No hay partidos con esos filtros');
    }
}
