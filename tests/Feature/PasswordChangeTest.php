<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->player = User::factory()->create(['password' => 'AnteriorPrueba09!', 'remember_token' => 'token-ficticio']);
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['current_password' => 'AnteriorPrueba09!', 'password' => 'NuevaPrueba09!', 'password_confirmation' => 'NuevaPrueba09!'], $changes);
    }

    public function test_correct_password_changes_hash_rotates_remember_token_and_keeps_current_session(): void
    {
        $this->post('/login', ['email' => $this->player->email, 'password' => 'AnteriorPrueba09!'])->assertRedirect();
        $this->get('/profile')->assertOk();
        $beforeId = session()->getId();
        $this->from('/profile')->patch('/profile/password', $this->payload())->assertRedirect('/profile')->assertSessionHasNoErrors();
        $current = $this->player->fresh();
        $this->assertTrue(Hash::check('NuevaPrueba09!', $current->password));
        $this->assertFalse(Hash::check('AnteriorPrueba09!', $current->password));
        $this->assertNotSame('token-ficticio', $current->remember_token);
        $this->assertNotSame($beforeId, session()->getId());
        $this->get('/profile')->assertOk();
        $this->assertAuthenticatedAs($current);
    }

    public function test_wrong_current_password_preserves_hash_and_never_flashes_passwords(): void
    {
        $before = $this->player->password;
        $this->actingAs($this->player)->from('/profile')->patch('/profile/password', $this->payload(['current_password' => 'Incorrecta09!']))->assertRedirect('/profile')->assertSessionHasErrors('current_password');
        $this->assertSame($before, $this->player->fresh()->password);
        foreach (['current_password', 'password', 'password_confirmation'] as $field) {
            $this->assertNull(session()->getOldInput($field));
        }
    }

    public function test_confirmation_length_and_multibyte_limits_are_validated(): void
    {
        $this->actingAs($this->player);
        foreach ([['password_confirmation' => 'Diferente09!'], ['password' => 'corta', 'password_confirmation' => 'corta'],
            ['password' => str_repeat('á', 40), 'password_confirmation' => str_repeat('á', 40)],
            ['password' => "ConNulo09!\0", 'password_confirmation' => "ConNulo09!\0"]] as $change) {
            $this->patchJson('/profile/password', $this->payload($change))->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->assertTrue(Hash::check('AnteriorPrueba09!', $this->player->fresh()->password));
    }

    public function test_hash_looking_text_is_hashed_as_the_actual_new_password(): void
    {
        $literal = Hash::make('TextoQuePareceHash09!');
        $this->actingAs($this->player)->patch('/profile/password', $this->payload(['password' => $literal, 'password_confirmation' => $literal]))->assertSessionHasNoErrors();
        $this->assertNotSame($literal, $this->player->fresh()->password);
        $this->assertTrue(Hash::check($literal, $this->player->fresh()->password));
    }

    public function test_extra_user_id_does_not_change_another_account(): void
    {
        $other = User::factory()->create(['password' => 'OtraPrueba09!']);
        $this->actingAs($this->player)->patch('/profile/password', $this->payload(['user_id' => $other->id, 'role' => 'admin']))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('OtraPrueba09!', $other->fresh()->password));
        $this->assertSame('player', $this->player->fresh()->role);
    }

    public function test_previous_session_is_rejected_on_next_protected_request(): void
    {
        $this->post('/login', ['email' => $this->player->email, 'password' => 'AnteriorPrueba09!'])->assertRedirect();
        $this->get('/profile')->assertOk();
        $oldSession = session()->all();
        $this->patch('/profile/password', $this->payload())->assertSessionHasNoErrors();
        $this->get('/profile')->assertOk();
        // Un nuevo guard recupera la cuenta de la DB, como haría otra petición real.
        session()->replace($oldSession);
        session()->save();
        Auth::forgetGuards();
        $this->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_repeated_wrong_passwords_are_limited_and_recover_after_waiting(): void
    {
        $this->actingAs($this->player);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->patchJson('/profile/password', $this->payload(['current_password' => 'Incorrecta09!']))->assertUnprocessable();
        }
        $this->patchJson('/profile/password', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertTrue(Hash::check('AnteriorPrueba09!', $this->player->fresh()->password));
        $this->travel(61)->seconds();
        $this->patch('/profile/password', $this->payload())->assertRedirect();
        $this->assertTrue(Hash::check('NuevaPrueba09!', $this->player->fresh()->password));
        $this->travelBack();
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->patch('/profile/password', $this->payload())->assertRedirect('/login');
        $this->assertTrue(Hash::check('AnteriorPrueba09!', $this->player->fresh()->password));
    }
}
