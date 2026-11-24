<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $key = 'password-change:'.hash('sha256', $request->user()->id.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['current_password' => 'Demasiados intentos. Espera un minuto antes de volver a probar.']);
        }
        RateLimiter::hit($key, 60);
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:1000'],
            'password' => PasswordRules::creation(),
        ]);
        DB::transaction(function () use ($request, $data): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'La contraseña actual no es correcta.']);
            }
            $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
            Auth::setUser($user);
            // auth.session revisa el hash en las siguientes peticiones de otros dispositivos.
            Auth::logoutOtherDevices($data['password']);
        }, 3);
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return back()->with('ok', 'Contraseña actualizada. Las otras sesiones deberán volver a entrar.');
    }
}
