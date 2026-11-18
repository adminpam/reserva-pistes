{{-- Enlaces compartidos por escritorio y menú móvil. Las páginas privadas
     conservan el middleware auth y llevan al acceso cuando hace falta. --}}
<a href="{{ route('home') }}" @class(['active' => request()->routeIs('home', 'clubs.*')])>Mapa</a>
<a href="{{ route('matches.index') }}" @class(['active' => request()->routeIs('matches.index')])>Partidos abiertos</a>
<a href="{{ route('bookings.index') }}" @class(['active' => request()->routeIs('bookings.*')])>Mis reservas</a>
<a href="{{ route('matches.mine') }}" @class(['active' => request()->routeIs('matches.mine')])>Mis partidos</a>
@auth
    @if(auth()->user()->isManager())
        <a href="{{ route('manager.courts.index') }}" @class(['active' => request()->routeIs('manager.*')])>Mi club</a>
    @endif
    @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.clubs.index') }}" @class(['active' => request()->routeIs('admin.*')])>Administración</a>
    @endif
@endauth
