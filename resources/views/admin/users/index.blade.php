{{-- Админка пользователей: поиск, смена роли, удаление. --}}
@extends('layouts.app')
@section('title', 'Admin · Usuarios — ReservaPistes')

@section('content')
<div class="page-head">
    <h1>Usuarios</h1>
    <nav class="subnav">
        <a href="{{ route('admin.clubs.index') }}">Clubes</a>
        <a href="{{ route('admin.sports.index') }}">Deportes</a>
        <a class="active" href="{{ route('admin.users.index') }}">Usuarios</a>
    </nav>
</div>

<form class="filters" method="GET" action="{{ route('admin.users.index') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre o correo…">
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

@forelse($users as $user)
    <div class="card row-card">
        <div>
            <strong>{{ $user->name }}</strong>
            <p class="muted">{{ $user->email }}</p>
        </div>
        <div class="row-actions">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="inline-form">
                @csrf @method('PATCH')
                <select name="role">
                    <option value="player" @selected($user->role === 'player')>Jugador</option>
                    <option value="manager" @selected($user->role === 'manager')>Gestor</option>
                    <option value="admin" @selected($user->role === 'admin')>Admin</option>
                </select>
                <button class="btn btn-ghost" type="submit">Cambiar rol</button>
            </form>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  onsubmit="return confirm('¿Eliminar este usuario?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        </div>
    </div>
@empty
    <p class="muted">Sin resultados.</p>
@endforelse

{{ $users->links() }}
@endsection
