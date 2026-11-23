{{-- Roles y usuarios; la cuenta actual no ofrece el botón de eliminación. --}}
@extends('layouts.app')
@section('title', 'Administración · Usuarios — Reserva Pistes')
@section('banner')@include('partials.banner', ['title' => 'Administración', 'subtitle' => 'Gestiona los clubes, deportes y usuarios de la plataforma.'])@endsection
@section('content')
@include('admin._nav')
<form class="rp-filter-bar rp-filter-bar--search" method="GET" action="{{ route('admin.users.index') }}"><input type="search" name="q" value="{{ request('q') }}" placeholder="Nombre o correo…" aria-label="Buscar usuario"><button class="btn btn-primary" type="submit">Buscar</button></form>
<div class="rp-list">
@forelse($users as $user)
    <article class="rp-panel rp-admin-row">
        <div class="rp-user-info"><span class="avatar">@include('partials.user-avatar', ['avatarUser' => $user])</span><div><h3>{{ $user->name }}</h3><p>{{ $user->email }}</p><p>Miembro desde {{ $user->created_at->translatedFormat('F \d\e Y') }}</p></div></div>
        <div class="rp-admin-actions">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PATCH')<div class="rp-field"><label for="role-{{ $user->id }}">Rol</label><select id="role-{{ $user->id }}" name="role" @disabled($user->id === auth()->id())>@foreach(['player' => 'Jugador', 'manager' => 'Gestor', 'admin' => 'Administrador'] as $value => $label)<option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>@endforeach</select></div>@if($user->id !== auth()->id())<button class="btn btn-ghost btn-sm" type="submit">Cambiar</button>@endif</form>
            @if($user->id === auth()->id())<span class="rp-badge rp-badge--muted">Tu cuenta</span>@else<form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Solo se puede eliminar una cuenta sin clubes, reservas ni participaciones. ¿Eliminar esta cuenta?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Eliminar</button></form>@endif
        </div>
    </article>
@empty
    @include('partials.empty-state', ['title' => 'Sin resultados', 'text' => 'Prueba con otro nombre o correo.'])
@endforelse
</div>
{{ $users->links('partials.pagination') }}
@endsection
