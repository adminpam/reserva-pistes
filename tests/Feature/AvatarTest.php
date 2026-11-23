<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    // PNG sintéticos, sin personas ni metadatos. No requieren GD en el equipo.
    private const GREEN = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAIAAAAlC+aJAAAAeUlEQVR4nO3PQQkAMAzAwKqbiPkXUhF7HINABFxm7vk7L2hACxrQgga0oAEtaEALGtCCBrSgAS1oQAsa0IIGtKABLWhACxrQgga0oAEtaEALGtCCBrSgAS1oQAsa0IIGtKABLWhACxrQgga0oAEtaEALGtCCBrTgrQXzhwCXLd+u9QAAAABJRU5ErkJggg==';
    private const AMBER = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAIAAAAlC+aJAAAAeklEQVR4nO3PUQkAIBTAwBfC/inspyH8OITBAtzm7PV1wwUNaEEDWtCAFjSgBQ1oQQNa0IAWNKAFDWhBA1rQgBY0oAUNaEEDWtCAFjSgBQ1oQQNa0IAWNKAFDWhBA1rQgBY0oAUNaEEDWtCAFjSgBQ1oQQNa0IAWPHYBFktxpWwDqeIAAAAASUVORK5CYII=';
    private const TINY = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAFUlEQVR4nGNgiHIjDY1qGNUwfDUAAMtcoAHff+C0AAAAAElFTkSuQmCC';

    private const JPEG = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCABAAEADASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDg6KKK2PlAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigD/9k=';
    private const WEBP = 'UklGRkgAAABXRUJQVlA4IDwAAACQAwCdASpAAEAAPm02mEkkIyKhIggAgA2JaQAAEDdTUAV4hbkAAP7x3I87HuD///mCf+/3/f7yXAAAAAA=';

    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->player = User::factory()->create();
        Storage::fake('public');
    }

    private function image(string $content = self::GREEN): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('avatar.png', base64_decode($content));
    }

    private function upload(string $content = self::GREEN): void
    {
        $this->actingAs($this->player)->post('/profile/avatar', ['avatar' => $this->image($content)])->assertSessionHasNoErrors();
    }

    public function test_photo_is_saved_in_database_without_files_on_public_disk(): void
    {
        $this->upload();
        $bytes = base64_decode(self::GREEN);
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'mime_type' => 'image/png', 'image_base64' => self::GREEN]);
        $this->assertSame(hash('sha256', $bytes), $this->player->fresh()->avatar_version);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertArrayNotHasKey('image_base64', UserAvatar::findOrFail($this->player->id)->toArray());
        $this->assertArrayNotHasKey('image_base64', $this->player->fresh()->toArray());
    }

    public function test_jpeg_and_webp_are_accepted_without_a_server_image_encoder(): void
    {
        $this->actingAs($this->player);
        foreach ([['jpg', 'image/jpeg', self::JPEG], ['webp', 'image/webp', self::WEBP]] as [$extension, $mime, $encoded]) {
            $this->post('/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('avatar.'.$extension, base64_decode($encoded))])->assertSessionHasNoErrors();
            $this->get('/users/'.$this->player->id.'/avatar')->assertOk()->assertHeader('Content-Type', $mime)->assertContent(base64_decode($encoded));
        }
        $this->assertDatabaseCount('user_avatars', 1);
    }

    public function test_authenticated_image_response_has_correct_bytes_headers_and_private_cache(): void
    {
        $this->upload();
        $url = $this->player->fresh()->avatarUrl();
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')->assertContent(base64_decode(self::GREEN));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->get($url, ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
    }

    public function test_replacing_photo_changes_version_and_url_without_duplicate_rows(): void
    {
        $this->upload();
        $old = $this->player->fresh()->avatarUrl();
        $this->upload(self::AMBER);
        $new = $this->player->fresh()->avatarUrl();
        $this->assertNotSame($old, $new);
        $this->assertDatabaseCount('user_avatars', 1);
        $this->get($new)->assertOk()->assertContent(base64_decode(self::AMBER));
    }

    public function test_invalid_or_oversized_upload_preserves_previous_photo(): void
    {
        $this->upload();
        $version = $this->player->fresh()->avatar_version;
        foreach ([UploadedFile::fake()->createWithContent('avatar.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->createWithContent('falsa.png', 'esto no es una imagen'),
            UploadedFile::fake()->createWithContent('avatar.png', base64_decode(self::GREEN).str_repeat('x', 513 * 1024)),
            $this->image(self::TINY)] as $invalid) {
            $this->postJson('/profile/avatar', ['avatar' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('avatar');
        }
        $this->assertSame($version, $this->player->fresh()->avatar_version);
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::GREEN]);
    }

    public function test_photo_changes_only_the_authenticated_user_even_with_extra_id(): void
    {
        $other = User::factory()->create();
        $this->actingAs($this->player)->post('/profile/avatar', ['avatar' => $this->image(), 'user_id' => $other->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id]);
        $this->assertDatabaseMissing('user_avatars', ['user_id' => $other->id]);
        $this->assertNull($other->fresh()->avatar_version);
    }

    public function test_removing_photo_is_repeatable_and_returns_to_initials(): void
    {
        $this->upload();
        $url = $this->player->fresh()->avatarUrl();
        $this->delete('/profile/avatar')->assertSessionHasNoErrors();
        $this->delete('/profile/avatar')->assertSessionHasNoErrors();
        $this->assertNull($this->player->fresh()->avatarUrl());
        $this->assertDatabaseCount('user_avatars', 0);
        $this->get($url)->assertNotFound();
        $this->actingAs($this->player->fresh())->get('/profile')->assertOk()->assertDontSee('Quitar foto');
    }

    public function test_photo_survives_new_user_and_guard_instances(): void
    {
        $this->upload();
        Auth::forgetGuards();
        $this->actingAs(User::findOrFail($this->player->id))->get('/users/'.$this->player->id.'/avatar')->assertOk()->assertContent(base64_decode(self::GREEN));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_guests_cannot_upload_delete_or_read_photos(): void
    {
        $this->post('/profile/avatar', ['avatar' => $this->image()])->assertRedirect('/login');
        $this->delete('/profile/avatar')->assertRedirect('/login');
        $this->get('/users/'.$this->player->id.'/avatar')->assertRedirect('/login');
        $this->assertDatabaseCount('user_avatars', 0);
    }

    public function test_missing_photo_is_404_and_an_unused_account_deletion_removes_its_photo(): void
    {
        $this->actingAs($this->player)->get('/users/'.$this->player->id.'/avatar')->assertNotFound();
        $this->upload();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete('/admin/users/'.$this->player->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $this->player->id]);
        $this->assertDatabaseMissing('user_avatars', ['user_id' => $this->player->id]);
    }

    public function test_upload_limit_does_not_overwrite_photo_and_recovers_after_waiting(): void
    {
        $this->actingAs($this->player);
        foreach (range(1, 10) as $attempt) {
            $this->post('/profile/avatar', ['avatar' => $this->image()])->assertSessionHasNoErrors();
        }
        $this->postJson('/profile/avatar', ['avatar' => $this->image(self::AMBER)])->assertUnprocessable()->assertJsonValidationErrors('avatar');
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::GREEN]);
        $this->travel(61)->seconds();
        $this->post('/profile/avatar', ['avatar' => $this->image(self::AMBER)])->assertRedirect();
        $this->assertDatabaseHas('user_avatars', ['user_id' => $this->player->id, 'image_base64' => self::AMBER]);
        $this->travelBack();
    }
}
