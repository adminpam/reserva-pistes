{{-- Portada: hero breve y mapa de clubes con filtro por deporte.
     Los marcadores los pide app.js al endpoint /api/clubs. --}}
@extends('layouts.app')
@section('title', 'ReservaPistes — encuentra pista y rival')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
@endpush

@section('content')
<section class="hero">
    <div>
        <h1>Tu pista, tu partido,<br>a un clic.</h1>
        <p>Clubes reales de Sabadell, Terrassa y Barcelona. Reserva una hora o únete a un partido abierto de tu nivel.</p>
    </div>
    <img class="hero-art" src="{{ asset('img/hero.svg') }}" alt="" width="300" height="200">
</section>

<section class="map-layout" aria-label="Mapa de clubes">
    <aside class="card filter-panel">
        <h2>Deporte</h2>

        <div class="sport-list">
            <input type="radio" name="sport-filter" id="sp-all" value="" checked>
            <label for="sp-all">
                <img src="{{ asset('img/sports/icon-all.svg') }}" alt="">
                Todos
                <span class="dot"></span>
            </label>

            @foreach($sports as $sport)
                <input type="radio" name="sport-filter" id="sp-{{ $sport->slug }}" value="{{ $sport->slug }}">
                <label for="sp-{{ $sport->slug }}">
                    <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="">
                    {{ $sport->name }}
                    <span class="dot"></span>
                </label>
            @endforeach
        </div>

        <p class="club-count">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>
            </svg>
            <span id="club-count" aria-live="polite">Cargando…</span>
        </p>
    </aside>

    {{-- data-clubs-url: app.js lee de aquí a dónde pedir los marcadores --}}
    <div id="map" data-clubs-url="{{ route('api.clubs') }}"></div>
</section>
@endsection
