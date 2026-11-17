{{-- Navegacion inferior en movil: cuatro destinos, zonas tactiles de 60 px. --}}
<nav class="tabbar" aria-label="Navegación principal">
    <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home', 'clubs.*')])>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        <span>Mapa</span>
    </a>
    <a href="{{ auth()->check() ? route('matches.index') : route('login') }}" @class(['active' => request()->routeIs('matches.index')])>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
            <path d="M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
            <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/>
        </svg>
        <span>Partidos</span>
    </a>
    <a href="{{ auth()->check() ? route('bookings.index') : route('login') }}" @class(['active' => request()->routeIs('bookings.*')])>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
        </svg>
        <span>Reservas</span>
    </a>
    <a href="{{ auth()->check() ? route('profile.edit') : route('login') }}" @class(['active' => request()->routeIs('profile.*')])>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Perfil</span>
    </a>
</nav>
