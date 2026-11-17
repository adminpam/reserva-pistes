{{-- Administración de clubes: buscar, asignar gestor y eliminar. --}}
@extends('layouts.app')
@section('title', 'Administración · Clubes — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Administración',
        'subtitle' => 'Clubes, deportes y usuarios de la plataforma.',
        'image'    => \App\Support\Ui::sportCover('tenis'),
    ])
@endsection

@section('content')
@include('admin._nav')

<form class="card filters" method="GET" action="{{ route('admin.clubs.index') }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar club…" aria-label="Buscar club">
    <button class="btn btn-ghost" type="submit">Buscar</button>
    <span class="spacer"></span>
    <span class="tiny">Los clubes se importan desde OpenStreetMap con <code class="mono">php artisan clubs:import</code></span>
</form>

@forelse($clubs as $club)
    <article class="card row-card">
        <div class="grow">
            <h3>{{ $club->name }}</h3>
            <p class="muted">{{ $club->address ?: 'Dirección pendiente' }}@if($club->city), {{ $club->city }}@endif</p>
        </div>

        <span class="chip">{{ $club->courts_count }} {{ $club->courts_count === 1 ? 'pista' : 'pistas' }}</span>

        <div class="row-actions">
            <form method="POST" action="{{ route('admin.clubs.update', $club) }}" class="inline" style="gap:8px">
                @csrf @method('PATCH')
                <select name="manager_id" style="width:auto;min-width:170px;height:42px" aria-label="Gestor">
                    <option value="">Sin gestor</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" @selected($club->manager_id === $manager->id)>
                            {{ $manager->name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-ghost btn-sm" type="submit">Asignar</button>
            </form>

            <form method="POST" action="{{ route('admin.clubs.destroy', $club) }}"
                  onsubmit="return confirm('Se eliminará el club con sus pistas y reservas. ¿Seguro?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
        </div>
    </article>
@empty
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>No hay clubes</h3>
        <p>Ejecuta <code class="mono">php artisan clubs:import</code> para traerlos de OpenStreetMap.</p>
    </div>
@endforelse

{{ $clubs->links() }}
@endsection
