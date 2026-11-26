<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDemoCheckout;
use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDemoCheckout {
        setUp as private setUpCheckout;
    }

    protected function setUp(): void
    {
        $this->setUpCheckout();
        app()->setLocale('es');
    }

    public function test_empty_registration_returns_spanish_errors_and_a_spanish_json_summary(): void
    {
        $response = $this->postJson('/register', [])->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Completa el campo nombre.')
            ->assertJsonPath('errors.email.0', 'Completa el campo correo electrónico.')
            ->assertJsonPath('errors.password.0', 'Completa el campo contraseña.');
        $this->assertSame('Completa el campo nombre. (y 2 errores más)', $response->json('message'));
        $this->assertDatabaseCount('users', 1); // Solo la cuenta ficticia del escenario.
        $this->assertGuest();
    }

    public function test_legacy_english_locale_still_uses_the_current_spanish_interface(): void
    {
        app()->setLocale('en');
        $this->postJson('/login', [])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Completa el campo correo electrónico.')
            ->assertJsonPath('errors.password.0', 'Completa el campo contraseña.')
            ->assertJsonPath('message', 'Completa el campo correo electrónico. (y 1 error más)');
    }

    public function test_invalid_email_and_short_password_explain_what_to_correct(): void
    {
        $this->postJson('/register', ['name' => 'Cuenta de prueba', 'email' => 'correo-invalido',
            'password' => 'corta', 'password_confirmation' => 'corta'])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Introduce un correo electrónico válido.')
            ->assertJsonPath('errors.password.0', 'El campo contraseña debe tener al menos 8 caracteres.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_confirmation_remains_attached_to_the_password_field(): void
    {
        $this->postJson('/register', ['name' => 'Cuenta de prueba', 'email' => 'otra@example.test',
            'password' => 'PruebaSegura16!', 'password_confirmation' => 'Distinta16!'])->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Las contraseñas no coinciden. Repite la misma contraseña en ambos campos.');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_html_validation_keeps_safe_input_and_never_flashes_passwords(): void
    {
        $this->from('/register')->post('/register', ['name' => 'Cuenta de prueba',
            'email' => '  OTRA@EXAMPLE.TEST ', 'password' => 'corta', 'password_confirmation' => 'corta',
            'current_password' => 'Secreto de prueba', 'payment' => ['number' => '4242424242424242']])
            ->assertRedirect('/register')->assertSessionHasErrors([
                'password' => 'El campo contraseña debe tener al menos 8 caracteres.',
            ]);
        $this->assertSame('Cuenta de prueba', session()->getOldInput('name'));
        $this->assertSame('otra@example.test', session()->getOldInput('email'));
        foreach (['password', 'password_confirmation', 'current_password', 'payment'] as $field) {
            $this->assertNull(session()->getOldInput($field));
        }
    }

    public function test_array_instead_of_login_password_is_rejected_with_a_readable_message(): void
    {
        $this->postJson('/login', ['email' => 'jugador@example.test', 'password' => ['incorrecto']])
            ->assertUnprocessable()->assertJsonPath('errors.password.0', 'El campo contraseña debe contener texto.');
        $this->assertGuest();
    }

    public function test_nested_level_errors_keep_the_sport_key_and_do_not_partially_save_the_name(): void
    {
        $sportId = $this->court->sport_id;
        $response = $this->actingAs($this->player)->patchJson('/profile', [
            'name' => 'No debe guardarse', 'levels' => [$sportId => 6],
        ])->assertUnprocessable()->assertJsonValidationErrors('levels.'.$sportId);
        $this->assertSame(['El campo nivel de juego no puede superar 5.'], $response->json('errors')['levels.'.$sportId]);
        $this->assertSame('Jugadora ficticia', $this->player->fresh()->name);
        $this->assertDatabaseCount('user_sport_levels', 0);
    }

    public function test_missing_current_password_uses_a_readable_label_without_changing_the_hash(): void
    {
        $before = $this->player->password;
        $this->actingAs($this->player)->patchJson('/profile/password', [
            'password' => 'CambioSeguro16!', 'password_confirmation' => 'CambioSeguro16!',
        ])->assertUnprocessable()->assertJsonPath('errors.current_password.0', 'Completa el campo contraseña actual.');
        $this->assertSame($before, $this->player->fresh()->password);
    }

    public function test_court_price_and_hours_errors_are_readable_and_never_create_a_partial_court(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $this->court->club->update(['manager_id' => $manager->id]);
        $this->actingAs($manager)->postJson('/manager/courts', [
            'club_id' => $this->court->club_id, 'sport_id' => $this->court->sport_id,
            'name' => 'Pista ficticia', 'price_euros' => 'texto', 'open_hour' => 20, 'close_hour' => 19,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.price_euros.0', 'El campo precio por hora debe ser un número.')
            ->assertJsonPath('errors.close_hour.0', 'La hora de cierre debe ser posterior a la hora de apertura.');
        $this->assertDatabaseCount('courts', 1);
    }

    public function test_admin_role_error_keeps_the_role_key_and_the_previous_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patchJson('/admin/users/'.$this->player->id, ['role' => 'inventado'])
            ->assertUnprocessable()->assertJsonPath('errors.role.0', 'Selecciona un rol válido: jugador, gestor o administrador.');
        $this->assertSame('player', $this->player->fresh()->role);
    }

    public function test_invalid_booking_fields_do_not_create_a_hold_or_booking(): void
    {
        $this->actingAs($this->player)->postJson('/bookings', $this->selection([
            'date' => '2026-02-30', 'hour' => 'texto', 'checkout_key' => 'referencia-inventada',
        ]))->assertUnprocessable()
            ->assertJsonPath('errors.date.0', 'Elige una fecha válida en el calendario.')
            ->assertJsonPath('errors.hour.0', 'El campo hora debe ser un número entero.')
            ->assertJsonPath('errors.checkout_key.0', 'La solicitud de reserva no es válida. Recarga la ficha del club antes de reservar.');
        $this->assertDatabaseCount('booking_checkouts', 0);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_payment_errors_keep_nested_keys_and_do_not_confirm_the_booking(): void
    {
        $checkout = $this->begin();
        $response = $this->postJson(route('checkout.pay', $checkout), ['payment' => ['expiry' => 'incorrecta']])
            ->assertUnprocessable()->assertJsonValidationErrors(['payment.holder', 'payment.number', 'payment.cvc', 'payment.expiry']);
        $errors = $response->json('errors');
        $this->assertSame(['Completa el campo titular de prueba.'], $errors['payment.holder']);
        $this->assertSame(['Completa el campo número de tarjeta de prueba.'], $errors['payment.number']);
        $this->assertSame(['La caducidad debe tener formato MM/AA.'], $errors['payment.expiry']);
        $this->assertSame('pending', $checkout->fresh()->status);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('demo_payments', 0);
    }

    public function test_html_payment_errors_never_restore_the_card_input(): void
    {
        $checkout = $this->begin();
        $url = route('checkout.show', $checkout);
        $this->from($url)->post(route('checkout.pay', $checkout), ['payment' => [
            'holder' => 'Persona Demo', 'number' => '4242424242424242', 'expiry' => 'incorrecta', 'cvc' => '123',
        ]])->assertRedirect($url)->assertSessionHasErrors(['payment.expiry' => 'La caducidad debe tener formato MM/AA.']);
        $this->assertNull(session()->getOldInput('payment'));
        $this->assertSame('pending', $checkout->fresh()->status);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_malformed_sport_filter_keeps_its_index_without_showing_a_technical_field_name(): void
    {
        $response = $this->getJson('/api/clubs?'.http_build_query(['sports' => [['tenis-demo']]]))
            ->assertUnprocessable()->assertJsonValidationErrors('sports.0');
        $this->assertSame(['El campo deporte debe contener texto.'], $response->json('errors')['sports.0']);
    }

    public function test_availability_rejects_an_invalid_calendar_day_with_a_spanish_error(): void
    {
        $this->getJson('/clubs/'.$this->court->club_id.'/availability?date=2026-02-30')
            ->assertUnprocessable()->assertJsonPath('errors.date.0', 'Elige una fecha válida en el calendario.');
    }
}
