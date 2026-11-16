{{-- Pagina principal: hero breve + mapa Leaflet a pantalla ancha con filtro
     por deporte. Los marcadores se cargan por JS desde /api/clubs. --}}
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
    <img class="hero-art" src="{{ asset('img/hero.svg') }}" alt="">
</section>

<section class="map-section" aria-label="Mapa de clubes">
    <div class="map-toolbar">
        <label for="sport-filter">Deporte:</label>
        <select id="sport-filter">
            <option value="">Todos</option>
            @foreach($sports as $sport)
                <option value="{{ $sport->slug }}">{{ $sport->name }}</option>
            @endforeach
        </select>
        <span id="club-count" class="muted" aria-live="polite"></span>
    </div>

    {{-- data-clubs-url: app.js lee esta URL para pedir los marcadores. --}}
    <div id="map" data-clubs-url="{{ route('api.clubs') }}"></div>
</section>

<section class="sports-strip" aria-label="Deportes disponibles">
    @foreach($sports as $sport)
        <div class="sport-pill">
            <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="" width="22" height="22">
            {{ $sport->name }}
        </div>
    @endforeach
</section>
@endsection
