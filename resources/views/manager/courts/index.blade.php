{{-- Panel del gestor: sus clubes con sus pistas y un resumen de ocupación. --}}
@extends('layouts.app')
@section('title', 'Mi club — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Mi club',
        'subtitle' => 'Gestiona tus pistas y revisa las reservas.',
        'image'    => \App\Support\Ui::sportCover('futbol-sala'),
    ])
@endsection

@section('content')
@php
    $totalPistas = $clubs->sum(fn ($c) => $c->courts->count());
    $semana = $clubs->sum(fn ($c) => $c->courts->sum(fn ($p) => $p->bookings
        ->where('status', 'confirmed')
        ->where('starts_at', '>=', now()->startOfWeek())
        ->where('starts_at', '<=', now()->endOfWeek())->count()));
@endphp

<div class="stats">
    <div class="card stat">
        <span class="num">{{ $totalPistas }}</span>
        <p class="label">{{ $totalPistas === 1 ? 'pista' : 'pistas' }} en total</p>
    </div>
    <div class="card stat">
        <span class="num">{{ $semana }}</span>
        <p class="label">reservas esta semana</p>
    </div>
    <div class="card stat">
        <span class="num">{{ $clubs->count() }}</span>
        <p class="label">{{ $clubs->count() === 1 ? 'club gestionado' : 'clubes gestionados' }}</p>
    </div>
</div>

@forelse($clubs as $club)
    <div class="page-head">
        <h2 class="section-title" style="margin:0">{{ $club->name }}</h2>
        <div class="inline">
            <a class="btn btn-ghost" href="{{ route('manager.bookings.index') }}">Reservas del club</a>
            <a class="btn btn-primary" href="{{ route('manager.courts.create') }}">+ Nueva pista</a>
        </div>
    </div>

    <div class="stagger">
    @forelse($club->courts as $court)
        <article class="card row-card">
            <div class="sport-circle">
                <img src="{{ asset('img/sports/'.$court->sport->icon) }}" alt="">
            </div>

            <div class="grow">
                <h3>{{ $court->name }}</h3>
                <p class="muted">
                    {{ $court->sport->name }} · {{ $court->priceEuros() }} €/hora ·
                    {{ $court->open_hour }}:00–{{ $court->close_hour }}:00
                </p>
            </div>

            <span class="chip {{ $court->is_outdoor ? 'chip-amber' : '' }}">
                {{ $court->is_outdoor ? 'Exterior' : 'Interior' }}
            </span>

            <div class="row-actions">
                <a class="btn btn-ghost btn-sm" href="{{ route('manager.courts.edit', $court) }}">Editar</a>
                <form method="POST" action="{{ route('manager.courts.destroy', $court) }}"
                      onsubmit="return confirm('Se eliminará la pista y sus reservas. ¿Seguro?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                </form>
            </div>
        </article>
    @empty
        <div class="card"><p class="muted">Este club todavía no tiene pistas.</p></div>
    @endforelse
    </div>
@empty
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>No tienes ningún club asignado</h3>
        <p>Pide al administrador que te asigne uno para poder gestionar sus pistas.</p>
    </div>
@endforelse
@endsection
