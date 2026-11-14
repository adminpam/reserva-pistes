{{-- Plantilla base: cabecera con navegacion segun rol, mensajes flash
     y pie. Todo el CSS/JS propio entra por Vite (@vite). --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ReservaPistes')</title>
    <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="site-header">
    <a class="brand" href="{{ route('home') }}">
        <img src="{{ asset('img/logo.svg') }}" alt="" width="34" height="34">
        <span>Reserva<em>Pistes</em></span>
    </a>

    <nav class="main-nav" aria-label="Principal">
        <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Mapa</a>
        @auth
            <a href="{{ route('matches.index') }}" @class(['active' => request()->routeIs('matches.index')])>Partidos abiertos</a>
            <a href="{{ route('bookings.index') }}" @class(['active' => request()->routeIs('bookings.*')])>Mis reservas</a>
            <a href="{{ route('matches.mine') }}" @class(['active' => request()->routeIs('matches.mine')])>Mis partidos</a>
            @if(auth()->user()->isManager())
                <a href="{{ route('manager.courts.index') }}" @class(['active' => request()->routeIs('manager.*')])>Mi club</a>
            @endif
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.clubs.index') }}" @class(['active' => request()->routeIs('admin.*')])>Admin</a>
            @endif
        @endauth
    </nav>

    <div class="header-actions">
        <button type="button" id="sound-toggle" class="icon-btn" title="Sonidos de la interfaz" aria-pressed="false">🔇</button>
        @auth
            <a class="user-chip" href="{{ route('profile.edit') }}">
                <img src="{{ asset('img/avatar.svg') }}" alt="" width="26" height="26">
                {{ auth()->user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Salir</button>
            </form>
        @else
            <a class="btn btn-ghost" href="{{ route('login') }}">Entrar</a>
            <a class="btn btn-primary" href="{{ route('register') }}">Crear cuenta</a>
        @endauth
    </div>
</header>

<main class="site-main">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="site-footer">
    <p>ReservaPistes — proyecto educativo (M0617, IES Sabadell). Las reservas y los pagos son simulados.</p>
</footer>
</body>
</html>
