{{-- Pistas y cifras del gestor, calculadas a partir de sus clubes. --}}
@extends('layouts.app')
@section('title', 'Mi club — Reserva Pistes')
@section('banner')
@include('partials.banner', ['title' => 'Mi club', 'subtitle' => 'Gestiona tus pistas y revisa las reservas.'])
@endsection
@section('content')
@php
    $total = $clubs->sum(fn ($club) => $club->courts->count());
    $week = $clubs->sum(fn ($club) => $club->courts->sum(fn ($court) => $court->bookings->where('status', 'confirmed')->where('starts_at', '>=', now()->startOfWeek())->where('starts_at', '<=', now()->endOfWeek())->count()));
@endphp
<div class="rp-stat-grid">
    <div class="rp-panel rp-stat"><strong>{{ $total }}</strong><span>Pistas en tus clubes</span></div>
    <div class="rp-panel rp-stat"><strong>{{ $week }}</strong><span>Reservas esta semana</span></div>
    <div class="rp-panel rp-stat"><strong>{{ $clubs->count() }}</strong><span>Clubes gestionados</span></div>
</div>
<nav class="rp-tabs" aria-label="Mi club"><a class="active" href="{{ route('manager.courts.index') }}">Pistas</a><a href="{{ route('manager.bookings.index') }}">Reservas</a></nav>
@forelse($clubs as $club)
    <div class="rp-page-toolbar"><h2>{{ $club->name }}</h2><a class="btn btn-primary" href="{{ route('manager.courts.create') }}">+ Nueva pista</a></div>
    <div class="rp-list">
    @forelse($club->courts as $court)
        <article class="rp-panel rp-admin-row">
            <div class="rp-user-info"><span class="rp-sport-disc">@include('partials.sport-icon', ['slug' => $court->sport->slug])</span><div><h3>{{ $court->name }}</h3><p>{{ $court->sport->name }} · {{ \App\Support\Ui::euros($court->price_per_hour) }}/hora · {{ $court->is_outdoor ? 'Exterior' : 'Interior' }}</p><p>{{ sprintf('%02d:00', $court->open_hour) }}–{{ sprintf('%02d:00', $court->close_hour) }}</p></div></div>
            <div class="rp-admin-actions"><a class="btn btn-ghost" href="{{ route('manager.courts.edit', $court) }}">Editar</a><form method="POST" action="{{ route('manager.courts.destroy', $court) }}" data-confirm="Solo se puede eliminar una pista que nunca haya tenido reservas. ¿Eliminar esta pista?">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Eliminar</button></form></div>
        </article>
    @empty
        <div class="rp-panel"><p class="rp-muted">Este club todavía no tiene pistas publicadas.</p></div>
    @endforelse
    </div>
@empty
    @include('partials.empty-state', ['title' => 'No tienes ningún club asignado', 'text' => 'El administrador debe asignarte un club para que puedas gestionar sus pistas.'])
@endforelse
@endsection
