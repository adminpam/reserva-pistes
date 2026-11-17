{{-- Plantilla base. Cabecera fija en escritorio, barra inferior en movil,
     capa de sonido y pantalla de carga. Todo el CSS/JS propio entra por Vite. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#00372e">
    <title>@yield('title', 'ReservaPistes')</title>
    <link rel="icon" href="{{ asset('brand/logo-original.png') }}" type="image/png">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('brand/loading.css') }}">
    <link rel="preload" href="{{ asset('brand/manrope-800.woff2') }}" as="font" type="font/woff2" crossorigin>
    {{-- Solo se oculta el contenido si JavaScript está disponible. El respaldo
         evita bloquear la página si el archivo del cargador no se descarga. --}}
    <script>
        (() => {
            let skip = false;
            try {
                const url = new URL(window.location.href);
                skip = url.searchParams.get('rp_ready') === '1';
                if (skip) {
                    url.searchParams.delete('rp_ready');
                    window.history.replaceState(null, '', url.pathname + url.search + url.hash);
                    try { sessionStorage.setItem('rp-loader-seen', '1'); } catch { /* Opcional. */ }
                }
                skip = skip || sessionStorage.getItem('rp-loader-seen') === '1';
            } catch { /* El almacenamiento no es obligatorio. */ }
            if (skip) return;
            document.documentElement.classList.add('rp-loading');
            window.setTimeout(() => {
                document.documentElement.classList.remove('rp-loading');
                document.querySelectorAll('[data-rp-inert]').forEach((element) => {
                    element.inert = false;
                    element.removeAttribute('data-rp-inert');
                });
                const loader = document.querySelector('[data-rp-loader]');
                if (loader) loader.hidden = true;
            }, 15000);
        })();
    </script>
    <script src="{{ asset('brand/loading.js') }}" defer></script>
</head>
<body>

{{-- Carga inicial: una vez por sesión; la entrada estática evita repetirla. --}}
@include('partials.intro')

<header class="site-header">
    <a class="brand" href="{{ route('home') }}">
        @include('partials.brand-mark')
        <span>ReservaPistes</span>
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
                <a href="{{ route('admin.clubs.index') }}" @class(['active' => request()->routeIs('admin.*')])>Administración</a>
            @endif
        @endauth
    </nav>

    <div class="header-actions">
        <button type="button" id="sound-toggle" class="icon-btn"
                title="Sonidos de la interfaz" aria-pressed="false" aria-label="Activar sonidos">
            <svg class="snd-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="m23 9-6 6M17 9l6 6"/>
            </svg>
            <svg class="snd-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden>
                <path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
            </svg>
        </button>

        <div class="divider"></div>

        @auth
            <a class="user-chip" href="{{ route('profile.edit') }}">
                <span class="avatar">{{ \App\Support\Ui::initials(auth()->user()->name) }}</span>
                <span class="hide-sm">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit">Salir</button>
            </form>
        @else
            <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Entrar</a>
            <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Crear cuenta</a>
        @endauth
    </div>
</header>

@hasSection('banner')
    @yield('banner')
@endif

<main class="site-main">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="site-footer">
    <p>ReservaPistes — proyecto educativo (M0617, IES Sabadell). Las reservas y los pagos son simulados.</p>
</footer>

{{-- Barra de navegacion inferior: solo en movil --}}
@include('partials.tabbar')

</body>
</html>
