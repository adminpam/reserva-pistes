<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoundUiTest extends TestCase
{
    use RefreshDatabase;

    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    public function test_guest_pages_have_one_optional_sound_button_and_a_live_status(): void
    {
        foreach (['/login', '/register'] as $url) {
            $response = $this->get($url)->assertOk();
            $xpath = $this->dom($response->getContent());
            $this->assertSame(1, $xpath->query('//button[@id="sound-toggle" and @type="button" and @hidden and @aria-pressed="false" and @aria-describedby="sound-status"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="sound-status" and @role="status" and @aria-live="polite" and @hidden]')->length);
            $this->assertSame(1, $xpath->query('//*[@data-sound-divider and @hidden]')->length);
            $this->assertSame(1, $xpath->query('//script[@type="module" and contains(@src,"ui/sounds.js?v=")]')->length);
            $this->assertSame(1, $xpath->query('//link[contains(@href,"ui/sounds.css?v=")]')->length);
        }
    }

    public function test_authenticated_profile_keeps_a_single_sound_control_and_the_existing_forms(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/profile')->assertOk();
        $xpath = $this->dom($response->getContent());
        $this->assertSame(1, $xpath->query('//button[@id="sound-toggle"]')->length);
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="current_password"]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="password_confirmation"]')->length);
        $response->assertSee('ui/form-validation.js', false)->assertSee('ui/password-visibility.js', false);
    }
}
