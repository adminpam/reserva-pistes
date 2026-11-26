<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\Support\CreatesDemoCheckout;
use Tests\TestCase;

class FormValidationUiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDemoCheckout;

    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    private function state(string $html): array
    {
        $nodes = $this->dom($html)->query('//script[@id="rp-validation-state" and @type="application/json"]');
        $this->assertSame(1, $nodes->length);

        return json_decode($nodes->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_authentication_pages_load_validation_and_keep_native_constraints_without_javascript(): void
    {
        foreach (['/login', '/register'] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('ui/form-validation.css', false)->assertSee('ui/form-validation.js', false);
            $xpath = $this->dom($response->getContent());
            $this->assertSame(0, $xpath->query('//form[@novalidate]')->length);
            $this->assertSame(1, $xpath->query('//input[@name="email" and @required and @maxlength="150"]')->length);
            $this->assertSame(1, $xpath->query('//input[@name="password" and @type="password" and @required]')->length);
        }
        $xpath = $this->dom($this->get('/register')->getContent());
        $this->assertSame(1, $xpath->query('//input[@name="password" and @minlength="8" and @maxlength="72" and @aria-describedby="register-password-hint"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="register-password-hint"]')->length);
    }

    public function test_error_payload_contains_only_errors_and_form_identity_and_escapes_script_boundaries(): void
    {
        $unsafe = '</script><img src=x onerror=alert(1)>';
        $marker = json_encode(['/register', 'POST', '']);
        $errors = (new ViewErrorBag())->put('default', new MessageBag(['email' => [$unsafe]]));
        $response = $this->withSession(['errors' => $errors, '_old_input' => [
            '_validation_form' => $marker, 'email' => 'cuenta@example.test',
            'password' => 'SECRETO_PASSWORD_17', '_token' => 'SECRETO_CSRF_17',
            'payment' => ['number' => 'SECRETO_TARJETA_17'],
        ]])->get('/register')->assertOk();
        $state = $this->state($response->getContent());
        $this->assertSame(['form', 'errors'], array_keys($state));
        $this->assertSame($marker, $state['form']);
        $this->assertSame([$unsafe], $state['errors']['email']);
        $xpath = $this->dom($response->getContent());
        $this->assertSame(0, $xpath->query('//img[@onerror]')->length);
        $script = $xpath->query('//script[@id="rp-validation-state"]')->item(0)->textContent;
        foreach (['SECRETO_PASSWORD_17', 'SECRETO_CSRF_17', 'SECRETO_TARJETA_17'] as $secret) {
            $this->assertStringNotContainsString($secret, $script);
        }
        $this->assertSame(1, $xpath->query('//*[@data-validation-fallback and @role="alert" and @tabindex="-1"]')->length);
    }

    public function test_real_html_validation_preserves_the_marker_and_spanish_password_error(): void
    {
        $marker = json_encode(['/register', 'POST', '']);
        $this->from('/register')->post('/register', ['name' => 'Cuenta de prueba',
            'email' => 'nueva17@example.test', 'password' => 'corta', 'password_confirmation' => 'corta',
            '_validation_form' => $marker])->assertRedirect('/register')->assertSessionHasErrors('password');
        $response = $this->get('/register')->assertOk();
        $state = $this->state($response->getContent());
        $this->assertSame($marker, $state['form']);
        $this->assertSame(['El campo contraseña debe tener al menos 8 caracteres.'], $state['errors']['password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_profile_keeps_avatar_autosave_and_loads_validation_before_confirmation_handlers(): void
    {
        $response = $this->actingAs($this->player)->get('/profile')->assertOk();
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'ui/screens.js'), strpos($html, 'ui/form-validation.js'));
        $xpath = $this->dom($html);
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]')->length);
        $this->assertSame(1, $xpath->query('//form[@data-avatar-autosave]//input[@type="file" and @name="avatar"]')->length);
        $this->assertSame(0, $xpath->query('//script[@id="rp-validation-state"]')->length);
    }

    public function test_malformed_or_oversized_context_is_not_emitted_as_a_form_identity(): void
    {
        foreach ([['no-es-texto'], str_repeat('x', 1001)] as $marker) {
            $errors = (new ViewErrorBag())->put('default', new MessageBag(['email' => ['Introduce un correo electrónico válido.']]));
            $response = $this->withSession(['errors' => $errors, '_old_input' => ['_validation_form' => $marker]])->get('/login')->assertOk();
            $this->assertNull($this->state($response->getContent())['form']);
        }
    }
}
