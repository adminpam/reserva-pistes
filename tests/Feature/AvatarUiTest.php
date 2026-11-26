<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAvatar;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvatarUiTest extends TestCase
{
    use RefreshDatabase;

    private function dom(string $html): DOMXPath
    {
        $doc = new DOMDocument();
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($before);

        return new DOMXPath($doc);
    }

    public function test_profile_renders_a_circle_and_no_manual_photo_controls(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/profile')->assertOk()
            ->assertSee('data-avatar-autosave', false)->assertSee('ui/avatar-autosave.js', false)
            ->assertDontSee('Guardar foto')->assertDontSee('Quitar foto')->assertDontSee('Comprueba la vista previa')
            ->assertSee('rp-avatar-camera', false)->assertSee('rp-avatar-placeholder', false)->assertDontSee('rp-avatar-plus', false);
        $xpath = $this->dom($response->getContent());
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]//button[@data-avatar-pick and @type="button"]')->length);
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]//input[@type="file" and @name="avatar"]')->length);
        $this->assertSame('', $xpath->query('//form[@data-avatar-autosave]//input[@name="expected_avatar_version"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]//*[@role="status" and @aria-live="polite"]')->length);
    }

    public function test_profile_and_header_share_the_current_users_versioned_photo_hooks(): void
    {
        $user = User::factory()->create();
        $encoded = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAIAAAAlC+aJAAAAeUlEQVR4nO3PQQkAMAzAwKqbiPkXUhF7HINABFxm7vk7L2hACxrQgga0oAEtaEALGtCCBrSgAS1oQAsa0IIGtKABLWhACxrQgga0oAEtaEALGtCCBrSgAS1oQAsa0IIGtKABLWhACxrQgga0oAEtaEALGtCCBrTgrQXzhwCXLd+u9QAAAABJRU5ErkJggg==';
        $version = hash('sha256', base64_decode($encoded));
        UserAvatar::create(['user_id' => $user->id, 'mime_type' => 'image/png', 'image_base64' => $encoded]);
        $user->forceFill(['avatar_version' => $version])->save();
        $response = $this->actingAs($user)->get('/profile')->assertOk();
        $xpath = $this->dom($response->getContent());
        $nodes = $xpath->query('//*[@data-user-avatar="'.$user->id.'"]');
        $this->assertSame(2, $nodes->length);
        foreach ($nodes as $node) {
            $this->assertSame($version, $node->getAttribute('data-avatar-version'));
            $this->assertSame($user->avatarUrl(), $node->getAttribute('data-avatar-url'));
        }
        $this->assertSame($version, $xpath->query('//form[@data-avatar-autosave]//input[@name="expected_avatar_version"]')->item(0)->getAttribute('value'));
    }

    public function test_auto_upload_form_does_not_include_name_levels_or_password_inputs(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/profile')->assertOk();
        $xpath = $this->dom($response->getContent());
        $this->assertSame(0, $xpath->query('//form[@data-avatar-autosave]//input[@name="name" or @type="password" or starts-with(@name,"levels[")]')->length);
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]//input[@name="_token"]')->length);
        $this->assertSame(1, $xpath->query('//input[@id="public-name"]')->length);
        $this->assertSame(3, $xpath->query('//input[@type="password"]')->length);
    }
}
