{{-- Админка видов спорта: вместимость матча + создание/удаление. --}}
@extends('layouts.app')
@section('title', 'Admin · Deportes — ReservaPistes')

@section('content')
<div class="page-head">
    <h1>Deportes</h1>
    <nav class="subnav">
        <a href="{{ route('admin.clubs.index') }}">Clubes</a>
        <a class="active" href="{{ route('admin.sports.index') }}">Deportes</a>
        <a href="{{ route('admin.users.index') }}">Usuarios</a>
    </nav>
</div>

@foreach($sports as $sport)
    <div class="card row-card">
        <div class="court-info">
            <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="" width="26" height="26">
            <div>
                <strong>{{ $sport->name }}</strong>
                <p class="muted">{{ $sport->courts_count }} pistas</p>
            </div>
        </div>
        <div class="row-actions">
            <form method="POST" action="{{ route('admin.sports.update', $sport) }}" class="inline-form">
                @csrf @method('PATCH')
                <label>Jugadores/partido
                    <input type="number" name="players_per_match" min="2" max="30" value="{{ $sport->players_per_match }}">
                </label>
                <button class="btn btn-ghost" type="submit">Guardar</button>
            </form>
            <form method="POST" action="{{ route('admin.sports.destroy', $sport) }}"
                  onsubmit="return confirm('¿Eliminar este deporte?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        </div>
    </div>
@endforeach

<div class="card">
    <h2 class="h-small">Nuevo deporte</h2>
    <form method="POST" action="{{ route('admin.sports.store') }}" class="inline-form">
        @csrf
        <input type="text" name="name" placeholder="Nombre" required maxlength="60">
        <input type="number" name="players_per_match" placeholder="Jugadores/partido" min="2" max="30" required>
        <button class="btn btn-primary" type="submit">Crear</button>
    </form>
</div>
@endsection
