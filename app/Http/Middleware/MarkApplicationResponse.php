<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Distingue las respuestas de Laravel de la pantalla de arranque del proxy.
class MarkApplicationResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Reserva-Pistes-App', '1');

        return $response;
    }
}
