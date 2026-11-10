<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Регистрация и вход на сессиях Laravel, без стартер-китов —
// так весь поток аутентификации виден в одном файле и его легко объяснить.
class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    // Создаёт пользователя с ролью player и сразу логинит.
    // Пароль хешируется автоматически кастом 'hashed' в модели User.
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([...$data, 'role' => 'player']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('ok', '¡Bienvenido a ReservaPistes!');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    // Вход: Auth::attempt сверяет хеш пароля; regenerate() защищает
    // от фиксации сессии (session fixation).
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->onlyInput('email');
        }

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
}
