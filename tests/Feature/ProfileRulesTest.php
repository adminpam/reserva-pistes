<?php

namespace Tests\Feature;

use App\Models\Sport;
use App\Models\User;
use App\Models\UserSportLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private Sport $sport;
    private Sport $otherSport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->player = User::factory()->create(['name' => 'Perfil Ficticio', 'role' => 'player']);
        $this->sport = Sport::create(['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2]);
        $this->otherSport = Sport::create(['name' => 'Voleibol', 'slug' => 'voleibol', 'players_per_match' => 12]);
        UserSportLevel::create(['user_id' => $this->player->id, 'sport_id' => $this->sport->id, 'level' => 2]);
        $this->actingAs($this->player);
    }

    public function test_profile_updates_name_and_levels_in_one_request(): void
    {
        $this->patch('/profile', ['name' => 'Nuevo Perfil Ficticio', 'levels' => [$this->sport->id => 3, $this->otherSport->id => 5]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $this->player->id, 'name' => 'Nuevo Perfil Ficticio']);
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->sport->id, 'level' => 3]);
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->otherSport->id, 'level' => 5]);
    }

    public function test_unknown_sport_does_not_save_name_or_another_valid_level(): void
    {
        $this->patchJson('/profile', ['name' => 'No Debe Guardarse', 'levels' => [$this->sport->id => 5, 999999 => 3]])->assertUnprocessable()->assertJsonValidationErrors('levels');
        $this->assertSame('Perfil Ficticio', $this->player->fresh()->name);
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->sport->id, 'level' => 2]);
        $this->assertDatabaseCount('user_sport_levels', 1);
    }

    public function test_noncanonical_sport_keys_are_validation_errors(): void
    {
        foreach (['01', '-1', '0', 'tenis', '1e2', '999999999999999999999999999'] as $key) {
            $this->patchJson('/profile', ['name' => 'No Debe Guardarse', 'levels' => [$key => 3]])->assertUnprocessable()->assertJsonValidationErrors('levels');
        }
        $this->assertSame('Perfil Ficticio', $this->player->fresh()->name);
        $this->assertDatabaseCount('user_sport_levels', 1);
    }

    public function test_invalid_levels_and_arrays_do_not_partially_update(): void
    {
        foreach ([0, 6, 'incorrecto', [3]] as $level) {
            $this->patchJson('/profile', ['name' => 'No Debe Guardarse', 'levels' => [$this->sport->id => $level]])->assertUnprocessable();
        }
        $this->patchJson('/profile', ['name' => 'No Debe Guardarse', 'levels' => 'incorrecto'])->assertUnprocessable();
        $this->assertSame('Perfil Ficticio', $this->player->fresh()->name);
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->sport->id, 'level' => 2]);
    }

    public function test_no_game_removes_only_the_explicit_sport(): void
    {
        UserSportLevel::create(['user_id' => $this->player->id, 'sport_id' => $this->otherSport->id, 'level' => 4]);
        $this->patch('/profile', ['name' => 'Perfil Ficticio', 'levels' => [$this->sport->id => '']])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->sport->id]);
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->otherSport->id, 'level' => 4]);
    }

    public function test_omitting_levels_preserves_them_and_extra_fields_cannot_change_account(): void
    {
        $before = $this->player->fresh();
        $this->patch('/profile', ['name' => 'Nombre Permitido', 'role' => 'admin', 'email' => 'otro@example.test', 'password' => 'NoDebeCambiar09!', 'avatar_version' => 'false', 'user_id' => 99999])->assertSessionHasNoErrors();
        $after = $this->player->fresh();
        $this->assertSame('player', $after->role);
        $this->assertSame($before->email, $after->email);
        $this->assertSame($before->password, $after->password);
        $this->assertNull($after->avatar_version);
        $this->assertDatabaseHas('user_sport_levels', ['user_id' => $this->player->id, 'sport_id' => $this->sport->id, 'level' => 2]);
    }

    public function test_profile_only_changes_the_authenticated_user(): void
    {
        $other = User::factory()->create(['name' => 'Otra Persona Ficticia']);
        $this->patch('/profile', ['name' => 'Mi Nombre Nuevo', 'user_id' => $other->id, 'levels' => [$this->sport->id => 4]])->assertSessionHasNoErrors();
        $this->assertSame('Otra Persona Ficticia', $other->fresh()->name);
        $this->assertDatabaseMissing('user_sport_levels', ['user_id' => $other->id]);
        $this->assertSame('Mi Nombre Nuevo', $this->player->fresh()->name);
    }

    public function test_profile_contains_separate_forms_and_guest_is_redirected(): void
    {
        $this->get('/profile')->assertOk()->assertSee('multipart/form-data', false)->assertSee('Cambiar contraseña')->assertSee('Guardar foto')->assertSee('No juego');
        auth()->logout();
        $this->patch('/profile', ['name' => 'Sin Acceso'])->assertRedirect('/login');
        $this->assertSame('Perfil Ficticio', $this->player->fresh()->name);
    }
}
