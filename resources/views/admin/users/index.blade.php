{{-- Administración de usuarios: buscar, cambiar rol y eliminar. --}}
@extends('layouts.app')
@section('title', 'Administración · Usuarios — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Administración',
        'subtitle' => 'Clubes, deportes y usuarios de la plataforma.',
        'image'    => \App\Support\Ui::sportCover('badminton'),
    ])
@endsection

@section('content')
@include('admin._nav')

<form class="card filters" method="GET" action="{{ route('admin.users.index') }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Nombre o correo…" aria-label="Buscar usuario">
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

@forelse($users as $user)
    @php($soyYo = $user->id === auth()->id())
    <article class="card row-card">
        <span class="avatar">{{ \App\Support\Ui::initials($user->name) }}</span>

        <div class="grow">
            <h3>{{ $user->name }}</h3>
            <p class="muted">{{ $user->email }}</p>
            <p class="tiny" style="margin-top:3px">
                Se unió en {{ $user->created_at->translatedFormat('F \d\e Y') }}
            </p>
        </div>

        <div class="row-actions">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="inline" style="gap:8px">
                @csrf @method('PATCH')
                <select name="role" style="width:auto;min-width:130px;height:42px" aria-label="Rol">
                    <option value="player"  @selected($user->role === 'player')>Jugador</option>
                    <option value="manager" @selected($user->role === 'manager')>Gestor</option>
                    <option value="admin"   @selected($user->role === 'admin')>Admin</option>
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Cambiar</button>
            </form>

            @if($soyYo)
                <span class="chip chip-muted" title="Es tu cuenta">🔒 Tu cuenta</span>
            @else
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                      onsubmit="return confirm('¿Eliminar este usuario y todas sus reservas?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                </form>
            @endif
        </div>
    </article>
@empty
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>Sin resultados</h3>
        <p>Prueba con otro nombre o correo.</p>
    </div>
@endforelse

{{ $users->links() }}
@endsection
