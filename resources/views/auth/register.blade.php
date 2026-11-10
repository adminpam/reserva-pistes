{{-- Форма регистрации. Создаёт игрока (role=player); менеджеров и админов
     назначает администратор в админке. --}}
@extends('layouts.app')
@section('title', 'Crear cuenta — ReservaPistes')

@section('content')
<div class="auth-card card">
    <h1>Crear cuenta</h1>
    <p class="muted">Regístrate para reservar pistas y unirte a partidos abiertos.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="name">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100" autofocus>

        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="150">

        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <input id="password" name="password" type="password" required minlength="8">

        <label for="password_confirmation">Repite la contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8">

        <button class="btn btn-primary btn-block" type="submit">Crear cuenta</button>
    </form>

    <p class="muted">¿Ya tienes cuenta? <a href="{{ route('login') }}">Entra aquí</a>.</p>
</div>
@endsection
