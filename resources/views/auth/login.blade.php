{{-- Entrar: panel oscuro de marca a la izquierda y formulario a la derecha. --}}
@extends('layouts.app')
@section('title', 'Entrar — ReservaPistes')

@section('content')
<div class="split" style="margin:-0 -32px">
    <div class="split-dark">
        <span class="rp-auth-mark">@include('partials.brand-mark')</span>
        <h2>Tu pista, tu partido, a un clic.</h2>
        <span class="rule"></span>
        <p>Entra para reservar pistas, abrir partidos y ver con quién juegas esta semana.</p>
    </div>

    <div class="split-light">
        <div class="card">
            <h1 style="font-size:2.25rem;margin-bottom:24px">Entrar</h1>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="field-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>
                        </svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               placeholder="tu@email.com" required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="field-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input id="password" name="password" type="password"
                               placeholder="Tu contraseña" required autocomplete="current-password">
                    </div>
                </div>

                <label class="check" style="margin:18px 0 22px">
                    <input type="checkbox" name="remember" value="1"> Recuérdame
                </label>

                <button class="btn btn-primary btn-block" type="submit">Entrar</button>
            </form>

            <p class="muted" style="text-align:center;margin-top:20px">
                ¿No tienes cuenta? <a href="{{ route('register') }}">Créala aquí</a>
            </p>
        </div>
    </div>
</div>
@endsection
