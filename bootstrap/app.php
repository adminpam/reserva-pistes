<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Конфигурация приложения Laravel 12 (вместо старых Kernel-классов).
// Здесь регистрируется алиас role для middleware проверки ролей.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En produccion la app esta detras del proxy de Render: confiamos en
        // sus cabeceras X-Forwarded-* para que los enlaces se generen con https.
        $middleware->trustProxies(at: "*");

        $middleware->alias([
            "role" => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
