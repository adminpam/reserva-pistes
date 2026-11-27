<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_identified_without_changing_their_content(): void
    {
        $this->get('/login')->assertOk()->assertHeader('X-Reserva-Pistes-App', '1')->assertSee('Entrar');
    }

    public function test_readiness_keeps_its_image_and_does_not_create_a_session(): void
    {
        $response = $this->get('/brand/ready')->assertOk()
            ->assertHeader('X-Reserva-Pistes-App', '1')->assertHeaderMissing('Set-Cookie');
        $this->assertStringStartsWith('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_private_profile_keeps_the_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Jugador carga selectiva']);
        $this->actingAs($user)->get('/profile')->assertOk()
            ->assertHeader('X-Reserva-Pistes-App', '1')->assertSee('Jugador carga selectiva');
    }

    public function test_authentication_redirect_is_preserved(): void
    {
        $this->get('/profile')->assertRedirect('/login')->assertHeader('X-Reserva-Pistes-App', '1');
    }

    public function test_application_error_is_marked_instead_of_becoming_an_endless_loader(): void
    {
        $this->get('/ruta-inexistente-carga-selectiva')->assertNotFound()->assertHeader('X-Reserva-Pistes-App', '1');
    }
}
