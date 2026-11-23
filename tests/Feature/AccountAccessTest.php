<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $changes = []): array
    {
        return array_replace(['name' => 'Cuenta Ficticia', 'email' => 'cuenta09@example.test',
            'password' => 'RegistroPrueba09!', 'password_confirmation' => 'RegistroPrueba09!'], $changes);
    }

    public function test_registration_normalizes_email_hashes_password_and_always_creates_player(): void
    {
        $this->post('/register', $this->registration(['email' => '  Cuenta09@Example.test ', 'role' => 'admin', 'avatar_version' => 'inventada']))->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $user = User::sole();
        $this->assertSame('cuenta09@example.test', $user->email);
        $this->assertSame('player', $user->role);
        $this->assertNull($user->avatar_version);
        $this->assertTrue(Hash::check('RegistroPrueba09!', $user->password));
        $this->assertNotSame('RegistroPrueba09!', $user->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_legacy_uppercase_duplicate_without_creating_an_account(): void
    {
        User::factory()->create(['email' => 'Cuenta09@Example.test']);
        $this->from('/register')->post('/register', $this->registration())->assertRedirect('/register')->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
    }

    public function test_invalid_registration_never_saves_an_account(): void
    {
        foreach ([['password_confirmation' => 'Diferente09!'], ['password' => 'corta', 'password_confirmation' => 'corta'],
            ['password' => str_repeat('á', 40), 'password_confirmation' => str_repeat('á', 40)],
            ['password' => "ConNulo09!\0", 'password_confirmation' => "ConNulo09!\0"]] as $change) {
            $this->postJson('/register', $this->registration($change))->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_hashes_a_password_that_already_looks_like_a_hash(): void
    {
        $literal = Hash::make('HashComoTexto09!');
        $this->post('/register', $this->registration(['password' => $literal, 'password_confirmation' => $literal]))->assertSessionHasNoErrors();
        $this->assertNotSame($literal, User::sole()->password);
        $this->assertTrue(Hash::check($literal, User::sole()->password));
    }

    public function test_login_accepts_normalized_email_and_preserves_intended_url(): void
    {
        $user = User::factory()->create(['email' => 'CorreoAntiguo@Example.test', 'password' => 'AccesoPrueba09!']);
        $this->get('/profile')->assertRedirect('/login');
        $beforeId = session()->getId();
        $this->post('/login', ['email' => '  CORREOANTIGUO@example.test ', 'password' => 'AccesoPrueba09!'])->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($beforeId, session()->getId());
    }

    public function test_unknown_account_and_wrong_password_show_the_same_error_without_password_old_input(): void
    {
        $user = User::factory()->create(['password' => 'AccesoPrueba09!']);
        foreach ([$user->email, 'inexistente09@example.test'] as $email) {
            $this->from('/login')->post('/login', ['email' => $email, 'password' => 'Incorrecta09!'])->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales incorrectas.']);
            $this->assertGuest();
            $this->assertNull(session()->getOldInput('password'));
            $this->assertSame($email, session()->getOldInput('email'));
        }
    }

    public function test_five_failed_attempts_limit_case_variants_then_expire(): void
    {
        $user = User::factory()->create(['email' => 'limite09@example.test', 'password' => 'AccesoPrueba09!']);
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/login', ['email' => $attempt % 2 ? 'LIMITE09@example.test' : $user->email, 'password' => 'Incorrecta09!'])->assertRedirect();
        }
        $this->postJson('/login', ['email' => $user->email, 'password' => 'AccesoPrueba09!'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'AccesoPrueba09!'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->travelBack();
    }

    public function test_ip_limit_also_blocks_attempts_with_many_different_accounts(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->postJson('/login', ['email' => 'inexistente'.$attempt.'@example.test', 'password' => 'Incorrecta09!'])->assertRedirect();
        }
        $this->postJson('/login', ['email' => 'otra-inexistente@example.test', 'password' => 'Incorrecta09!'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_registration_has_an_ip_limit_that_expires(): void
    {
        foreach (range(1, 5) as $number) {
            $this->post('/register', $this->registration(['email' => 'registro'.$number.'@example.test']))->assertRedirect(route('home'));
            Auth::logout();
        }
        $this->postJson('/register', $this->registration())->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 5);
        $this->travel(61)->seconds();
        $this->post('/register', $this->registration())->assertRedirect(route('home'));
        $this->assertDatabaseCount('users', 6);
        $this->travelBack();
    }

    public function test_logout_invalidates_the_current_session_and_protected_routes_require_login(): void
    {
        $user = User::factory()->create(['password' => 'AccesoPrueba09!']);
        $this->post('/login', ['email' => $user->email, 'password' => 'AccesoPrueba09!'])->assertRedirect();
        $this->get('/profile')->assertOk();
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get('/profile')->assertRedirect('/login');
    }
}
