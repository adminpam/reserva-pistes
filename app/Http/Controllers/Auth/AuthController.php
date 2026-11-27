<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\EmailDomainWithExtension;
use App\Support\PasswordRules;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Las plantillas de acceso aprobadas se conservan; las reglas se aplican aquí.
class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $key = 'register:'.hash('sha256', (string) $request->ip());
        $this->ensureAvailable([$key => 5]);
        RateLimiter::hit($key, 60);
        $this->normalizeEmail($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['bail', 'required', 'email', 'max:150', new EmailDomainWithExtension(), function ($attribute, $value, $fail): void {
                if (User::whereRaw('LOWER(email) = ?', [$value])->exists()) {
                    $fail('Este correo ya está registrado.');
                }
            }],
            'password' => PasswordRules::creation(),
        ]);

        try {
            // Se calcula el hash incluso si el texto elegido parece un hash.
            $user = User::create([...$data, 'password' => Hash::make($data['password']), 'role' => 'player']);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['email' => 'Este correo ya está registrado.']);
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('ok', '¡Bienvenido a ReservaPistes!');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:1000'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $accountKey = 'login-account:'.hash('sha256', $data['email'].'|'.$request->ip());
        $ipKey = 'login-ip:'.hash('sha256', (string) $request->ip());
        $this->ensureAvailable([$accountKey => 5, $ipKey => 30]);
        // Conserva el acceso a cuentas antiguas que guardaban mayúsculas.
        $storedEmail = User::whereRaw('LOWER(email) = ?', [$data['email']])->value('email') ?? $data['email'];
        if (! Auth::attempt(['email' => $storedEmail, 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($accountKey, 60);
            RateLimiter::hit($ipKey, 60);

            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }
        RateLimiter::clear($accountKey);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('ok', 'Sesión iniciada.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('ok', 'Sesión cerrada.');
    }

    private function normalizeEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
    }

    private function ensureAvailable(array $limits): void
    {
        foreach ($limits as $key => $maximum) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                $seconds = RateLimiter::availableIn($key);
                throw ValidationException::withMessages(['email' => 'Demasiados intentos. Vuelve a probar en '.$seconds.' segundos.']);
            }
        }
    }
}
