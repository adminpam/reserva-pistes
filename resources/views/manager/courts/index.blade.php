{{-- Panel del gestor: sus clubes con sus pistas y acciones CRUD. --}}
@extends('layouts.app')
@section('title', 'Mi club — ReservaPistes')

@section('content')
<div class="page-head">
    <h1>Mis pistas</h1>
    <div>
        <a class="btn btn-ghost" href="{{ route('manager.bookings.index') }}">Reservas del club</a>
        <a class="btn btn-primary" href="{{ route('manager.courts.create') }}">+ Nueva pista</a>
    </div>
</div>

@forelse($clubs as $club)
    <h2 class="h-small">{{ $club->name }}</h2>
    @forelse($club->courts as $court)
        <div class="card row-card">
            <div>
                <strong>{{ $court->name }}</strong>
                <p class="muted">
                    {{ $court->sport->name }} · {{ $court->priceEuros() }} €/h ·
                    {{ $court->open_hour }}:00–{{ $court->close_hour }}:00 ·
                    {{ $court->is_outdoor ? 'Exterior' : 'Interior' }}
                </p>
            </div>
            <div class="row-actions">
                <a class="btn btn-ghost" href="{{ route('manager.courts.edit', $court) }}">Editar</a>
                <form method="POST" action="{{ route('manager.courts.destroy', $court) }}"
                      onsubmit="return confirm('¿Eliminar esta pista y sus reservas?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                </form>
            </div>
        </div>
    @empty
        <p class="muted">Este club todavía no tiene pistas.</p>
    @endforelse
@empty
    <div class="empty-state">
        <img src="{{ asset('img/empty.svg') }}" alt="" width="120">
        <p>No tienes ningún club asignado. Pide al administrador que te asigne uno.</p>
    </div>
@endforelse
@endsection
