{{-- Админка клубов: поиск, назначение менеджера, удаление.
     Новые клубы появляются через php artisan clubs:import. --}}
@extends('layouts.app')
@section('title', 'Admin · Clubes — ReservaPistes')

@section('content')
<div class="page-head">
    <h1>Clubes</h1>
    <nav class="subnav">
        <a class="active" href="{{ route('admin.clubs.index') }}">Clubes</a>
        <a href="{{ route('admin.sports.index') }}">Deportes</a>
        <a href="{{ route('admin.users.index') }}">Usuarios</a>
    </nav>
</div>

<form class="filters" method="GET" action="{{ route('admin.clubs.index') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar club…">
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

<p class="muted">Los clubes se importan desde OpenStreetMap con <code>php artisan clubs:import</code>.</p>

@forelse($clubs as $club)
    <div class="card row-card">
        <div>
            <strong>{{ $club->name }}</strong>
            <p class="muted">{{ $club->address }}@if($club->city), {{ $club->city }}@endif · {{ $club->courts_count }} pistas</p>
        </div>
        <div class="row-actions">
            <form method="POST" action="{{ route('admin.clubs.update', $club) }}" class="inline-form">
                @csrf @method('PATCH')
                <select name="manager_id">
                    <option value="">Sin gestor</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" @selected($club->manager_id === $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost" type="submit">Asignar</button>
            </form>
            <form method="POST" action="{{ route('admin.clubs.destroy', $club) }}"
                  onsubmit="return confirm('¿Eliminar este club con sus pistas y reservas?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        </div>
    </div>
@empty
    <p class="muted">No hay clubes. Ejecuta <code>php artisan clubs:import</code>.</p>
@endforelse

{{ $clubs->links() }}
@endsection
