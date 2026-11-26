<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AvatarJsonTest extends TestCase
{
    use RefreshDatabase;

    // Imágenes sintéticas de 64 x 64; no requieren GD ni datos personales.
    private const GREEN = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAIAAAAlC+aJAAAAeUlEQVR4nO3PQQkAMAzAwKqbiPkXUhF7HINABFxm7vk7L2hACxrQgga0oAEtaEALGtCCBrSgAS1oQAsa0IIGtKABLWhACxrQgga0oAEtaEALGtCCBrSgAS1oQAsa0IIGtKABLWhACxrQgga0oAEtaEALGtCCBrTgrQXzhwCXLd+u9QAAAABJRU5ErkJggg==';
    private const AMBER = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAIAAAAlC+aJAAAAeklEQVR4nO3PUQkAIBTAwBfC/inspyH8OITBAtzm7PV1wwUNaEEDWtCAFjSgBQ1oQQNa0IAWNKAFDWhBA1rQgBY0oAUNaEEDWtCAFjSgBQ1oQQNa0IAWNKAFDWhBA1rQgBY0oAUNaEEDWtCAFjSgBQ1oQQNa0IAWPHYBFktxpWwDqeIAAAAASUVORK5CYII=';

    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->player = User::factory()->create(['name' => 'Persona Demo', 'password' => 'PruebaAvatar14!']);
        $this->actingAs($this->player);
    }

    private function photo(string $content = self::GREEN): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('foto.png', base64_decode($content));
    }

    private function upload(array $fields = [], string $content = self::GREEN): TestResponse
    {
        return $this->post('/profile/avatar', ['avatar' => $this->photo($content)] + $fields, ['Accept' => 'application/json']);
    }

    private function greenVersion(): string
    {
        return hash('sha256', base64_decode(self::GREEN));
    }

    public function test_json_upload_returns_private_metadata_and_usable_image_url_without_flash(): void
    {
        $response = $this->upload(['expected_avatar_version' => ''])->assertOk()
            ->assertJsonPath('message', 'Foto de perfil actualizada.')
            ->assertJsonPath('avatar.version', $this->greenVersion())
            ->assertJsonPath('avatar.initials', 'PD');
        $this->assertSame(['message', 'avatar'], array_keys($response->json()));
        $this->assertSame(['url', 'version', 'initials'], array_keys($response->json('avatar')));
        $this->assertSame($this->player->fresh()->avatarUrl(), $response->json('avatar.url'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $response->assertSessionMissing('ok');
        $this->get($response->json('avatar.url'))->assertOk()->assertContent(base64_decode(self::GREEN));
    }

    public function test_replacement_with_current_version_changes_url_and_keeps_one_photo(): void
    {
        $first = $this->upload()->assertOk()->json('avatar');
        $second = $this->upload(['expected_avatar_version' => $first['version']], self::AMBER)->assertOk()->json('avatar');
        $this->assertNotSame($first['version'], $second['version']);
        $this->assertNotSame($first['url'], $second['url']);
        $this->assertDatabaseCount('user_avatars', 1);
        $this->get($second['url'])->assertOk()->assertContent(base64_decode(self::AMBER));
    }

    public function test_late_upload_returns_current_metadata_without_overwriting_newer_photo(): void
    {
        $first = $this->upload()->assertOk()->json('avatar');
        $newer = $this->upload(['expected_avatar_version' => $first['version']], self::AMBER)->assertOk()->json('avatar');
        $late = $this->upload(['expected_avatar_version' => $first['version']])->assertStatus(409)
            ->assertJsonPath('avatar', $newer);
        $this->assertStringContainsString('no-store', $late->headers->get('Cache-Control'));
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::AMBER]);
        $this->assertSame($newer['version'], $this->player->fresh()->avatar_version);
    }

    public function test_empty_initial_version_cannot_overwrite_a_photo_created_by_another_request(): void
    {
        $first = $this->upload()->assertOk()->json('avatar');
        $this->upload(['expected_avatar_version' => null], self::AMBER)->assertStatus(409)->assertJsonPath('avatar', $first);
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::GREEN]);
    }

    public function test_malformed_versions_are_validation_errors_and_keep_the_saved_photo(): void
    {
        $this->upload()->assertOk();
        foreach ([['nested'], 'invalida', str_repeat('a', 65)] as $value) {
            $this->upload(['expected_avatar_version' => $value], self::AMBER)
                ->assertUnprocessable()->assertJsonValidationErrors('expected_avatar_version');
        }
        $this->assertSame($this->greenVersion(), $this->player->fresh()->avatar_version);
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::GREEN]);
    }

    public function test_json_delete_returns_initials_and_can_be_repeated(): void
    {
        $first = $this->upload()->assertOk()->json('avatar');
        $response = $this->deleteJson('/profile/avatar', ['expected_avatar_version' => $first['version']])->assertOk()
            ->assertJsonPath('avatar', ['url' => null, 'version' => null, 'initials' => 'PD']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->deleteJson('/profile/avatar', ['expected_avatar_version' => null])->assertOk()->assertJsonPath('avatar.url', null);
        $this->assertNull($this->player->fresh()->avatar_version);
        $this->assertDatabaseCount('user_avatars', 0);
        $this->get($first['url'])->assertNotFound();
    }

    public function test_stale_delete_cannot_remove_a_more_recent_photo(): void
    {
        $first = $this->upload()->assertOk()->json('avatar');
        $newer = $this->upload(['expected_avatar_version' => $first['version']], self::AMBER)->assertOk()->json('avatar');
        $this->deleteJson('/profile/avatar', ['expected_avatar_version' => $first['version']])->assertStatus(409)
            ->assertJsonPath('avatar', $newer);
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::AMBER]);
    }

    public function test_json_upload_ignores_other_user_ids_and_does_not_expose_account_data(): void
    {
        $other = User::factory()->create();
        $response = $this->upload(['user_id' => $other->id, 'name' => 'Nombre manipulado', 'role' => 'admin'])->assertOk();
        $this->assertSame('PD', $response->json('avatar.initials'));
        $this->assertSame('Persona Demo', $this->player->fresh()->name);
        $this->assertSame('player', $this->player->fresh()->role);
        $this->assertNull($other->fresh()->avatar_version);
        $this->assertDatabaseMissing('user_avatars', ['user_id' => $other->id]);
        foreach (['image_base64', 'password', 'email', 'remember_token'] as $field) {
            $this->assertStringNotContainsString('"'.$field.'"', $response->getContent());
        }
    }

    public function test_guests_get_json_authentication_errors_without_saving_a_photo(): void
    {
        Auth::logout();
        Auth::forgetGuards();
        $this->upload()->assertUnauthorized();
        $this->deleteJson('/profile/avatar')->assertUnauthorized();
        $this->assertDatabaseCount('user_avatars', 0);
    }

    public function test_html_forms_still_redirect_and_report_stale_versions_as_field_errors(): void
    {
        $this->from('/profile')->post('/profile/avatar', ['avatar' => $this->photo()])->assertRedirect('/profile')
            ->assertSessionHas('ok', 'Foto de perfil actualizada.');
        $this->from('/profile')->post('/profile/avatar', [
            'avatar' => $this->photo(self::AMBER), 'expected_avatar_version' => str_repeat('0', 64),
        ])->assertRedirect('/profile')->assertSessionHasErrors('avatar');
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::GREEN]);
        $this->from('/profile')->delete('/profile/avatar')->assertRedirect('/profile')
            ->assertSessionHas('ok', 'Foto eliminada. Se muestran tus iniciales.');
    }

    public function test_invalidated_session_cannot_upload_through_the_json_response_path(): void
    {
        Auth::logout();
        Auth::forgetGuards();
        $this->post('/login', ['email' => $this->player->email, 'password' => 'PruebaAvatar14!'])->assertRedirect();
        $this->get('/profile')->assertOk();
        $oldSession = session()->all();
        User::whereKey($this->player->id)->update(['password' => Hash::make('NuevaAvatar14!')]);
        session()->replace($oldSession);
        session()->save();
        Auth::forgetGuards();
        $this->upload()->assertUnauthorized();
        $this->assertDatabaseCount('user_avatars', 0);
    }
}
