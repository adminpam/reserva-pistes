<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    // Se configura antes de que RefreshDatabase pueda ejecutar migraciones.
    // Incluso si existe DB_URL en el equipo, las pruebas usan una SQLite temporal.
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if (! $app->environment('testing')) {
            throw new LogicException('Las pruebas necesitan APP_ENV=testing. Ejecuta php artisan config:clear y repite.');
        }
        $app->make('config')->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
        ]);
        $app->make('db')->purge('sqlite');

        return $app;
    }

    // actingAs simula un acceso nuevo. Al cambiar de identidad no se hereda
    // el hash de sesión del usuario anterior (auth.session ahora está activo).
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $driver = $guard ?? $this->app['auth']->getDefaultDriver();
        if ($this->app['auth']->guard($driver)->id() !== $user->getAuthIdentifier()) {
            $session = $this->app['session']->driver();
            $session->start();
            $session->forget('password_hash_'.$driver);
            $session->save();
        }

        return parent::actingAs($user, $guard);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
