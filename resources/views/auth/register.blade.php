{{-- Crear cuenta: mismo reparto que entrar, con medidor de seguridad. --}}
@extends('layouts.app')
@section('title', 'Crear cuenta — ReservaPistes')

@section('content')
<div class="split">
    <div class="split-dark">
        <span class="rp-auth-mark">@include('partials.brand-mark')</span>
        <h2>Empieza a jugar esta semana.</h2>
        <span class="rule"></span>
        <p>Crea tu cuenta, elige tu nivel en cada deporte y únete a partidos de gente que juega como tú.</p>
    </div>

    <div class="split-light">
        <div class="card">
            <h1 style="font-size:2.25rem;margin-bottom:24px">Crear cuenta</h1>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="field">
                    <label for="name">Nombre</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}"
                           placeholder="Tu nombre" required maxlength="100" autofocus autocomplete="name">
                </div>

                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           placeholder="tu@email.com" required maxlength="150" autocomplete="email">
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres"
                           required minlength="8" autocomplete="new-password">
                    <div class="strength" id="strength"><i></i><i></i><i></i></div>
                    <p class="tiny" id="strength-text" style="margin-top:6px">&nbsp;</p>
                </div>

                <div class="field">
                    <label for="password_confirmation">Repite la contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           placeholder="La misma otra vez" required minlength="8" autocomplete="new-password">
                </div>

                <button class="btn btn-primary btn-block" type="submit" style="margin-top:8px">Crear cuenta</button>
            </form>

            <p class="muted" style="text-align:center;margin-top:20px">
                ¿Ya tienes cuenta? <a href="{{ route('login') }}">Entra aquí</a>
            </p>
        </div>
    </div>
</div>

<script>
// Medidor de seguridad: longitud, mezcla de mayúsculas/minúsculas, y dígitos o símbolos.
document.getElementById('password')?.addEventListener('input', (e) => {
    const v = e.target.value;
    const barra = document.getElementById('strength');
    const texto = document.getElementById('strength-text');
    let puntos = 0;
    if (v.length >= 8)                       puntos++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v))  puntos++;
    if (/\d/.test(v) || /[^\w\s]/.test(v))   puntos++;
    barra.className = 'strength' + (v ? ' w' + puntos : '');
    texto.textContent = !v ? ' '
        : puntos <= 1 ? 'Seguridad baja'
        : puntos === 2 ? 'Seguridad media'
        : 'Seguridad alta';
});
</script>
@endsection
