{{-- Pestañas compartidas por la administración. --}}
<nav class="rp-tabs" aria-label="Administración">
    <a href="{{ route('admin.clubs.index') }}" @class(['active' => request()->routeIs('admin.clubs.*')])>Clubes</a>
    <a href="{{ route('admin.sports.index') }}" @class(['active' => request()->routeIs('admin.sports.*')])>Deportes</a>
    <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>Usuarios</a>
</nav>
