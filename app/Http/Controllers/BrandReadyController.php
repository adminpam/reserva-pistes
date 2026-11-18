<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Responde con una imagen solo cuando Laravel ha atendido la petición.
 * La entrada estática puede comprobarla con Image sin cookies ni permisos CORS.
 */
class BrandReadyController extends Controller
{
    public function __invoke(): Response
    {
        return response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="2" height="2" viewBox="0 0 2 2"><path fill="#ffb433" d="M0 0h2v2H0z"/></svg>',
            200,
            [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
