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

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
