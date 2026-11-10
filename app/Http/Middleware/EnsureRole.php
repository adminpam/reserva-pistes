<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Ограничивает доступ к группе роутов по роли пользователя.
// Использование: Route::middleware('role:manager') или 'role:admin'.
// Не та роль — 403, чтобы игрок не попал в панель менеджера по прямой ссылке.
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
