{{-- Форма входа: email + пароль + «запомнить меня». --}}
@extends('layouts.app')
@section('title', 'Entrar — ReservaPistes')

@section('content')
<div class="auth-card card">
    <h1>Entrar</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required>

        <label class="check">
            <input type="checkbox" name="remember" value="1"> Recuérdame
        </label>

        <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>

    <p class="muted">¿No tienes cuenta? <a href="{{ route('register') }}">Créala aquí</a>.</p>
</div>
@endsection
