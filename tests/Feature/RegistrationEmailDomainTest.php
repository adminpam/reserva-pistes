<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\EmailDomainWithExtension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegistrationEmailDomainTest extends TestCase
{
    use RefreshDatabase;

    private function registration(string $email): array
    {
        return ['name' => 'Cuenta de prueba', 'email' => $email,
            'password' => 'RegistroSeguro17b!', 'password_confirmation' => 'RegistroSeguro17b!'];
    }

    public function test_domain_policy_matches_the_shared_frontend_examples_without_dns(): void
    {
        $examples = json_decode(file_get_contents(base_path('tests/Fixtures/registration-email-domains.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($examples['valid'] as $email) {
            $validator = Validator::make(['email' => $email], ['email' => ['required', new EmailDomainWithExtension()]]);
            $this->assertTrue($validator->passes(), $email);
        }
        foreach ($examples['invalid'] as $email) {
            $validator = Validator::make(['email' => $email], ['email' => ['required', new EmailDomainWithExtension()]]);
            $this->assertFalse($validator->passes(), $email);
            $this->assertSame([EmailDomainWithExtension::MESSAGE], $validator->errors()->get('email'));
        }
    }

    public function test_registration_rejects_single_label_email_as_json_without_creating_a_user(): void
    {
        $this->postJson('/register', $this->registration('david@reservapistes'))->assertUnprocessable()
            ->assertJsonPath('errors.email.0', EmailDomainWithExtension::MESSAGE);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_html_registration_keeps_safe_input_and_accepts_test_extension_after_correction(): void
    {
        $this->from('/register')->post('/register', $this->registration('david@reservapistes'))
            ->assertRedirect('/register')->assertSessionHasErrors(['email' => EmailDomainWithExtension::MESSAGE]);
        $this->assertSame('david@reservapistes', session()->getOldInput('email'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
        $this->assertDatabaseCount('users', 0);
        $this->get('/register')->assertOk();
        $this->post('/register', $this->registration('  DAVID@RESERVAPISTES.TEST  '))
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'david@reservapistes.test', 'role' => 'player']);
        $this->assertAuthenticated();
    }

    public function test_existing_single_label_account_can_still_log_in(): void
    {
        $user = User::factory()->create(['email' => 'david@reservapistes', 'password' => 'CuentaAnterior17b!']);
        $this->post('/login', ['email' => 'david@reservapistes', 'password' => 'CuentaAnterior17b!'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_only_registration_email_has_the_extra_native_pattern_and_hint(): void
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($this->get('/register')->assertOk()->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//input[@name="email" and @pattern and @aria-describedby="register-email-hint"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="register-email-hint"]')->length);
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($this->get('/login')->assertOk()->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $this->assertSame(0, (new \DOMXPath($document))->query('//input[@name="email" and @pattern]')->length);
    }
}
