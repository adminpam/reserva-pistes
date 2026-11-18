<?php

namespace Tests\Feature;

use Tests\TestCase;

// La comprobación de arranque debe funcionar sin usuario, sesión ni tablas de datos.
class BrandReadyTest extends TestCase
{
    public function test_readiness_returns_an_uncached_image_without_cookies(): void
    {
        $response = $this->get('/brand/ready');

        $response->assertOk();
        $response->assertHeaderMissing('Set-Cookie');
        $this->assertStringStartsWith('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertSee('width="2" height="2"', false);
    }
}
