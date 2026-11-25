{{-- Portada: filtros y mapa reales. La disposición móvil usa una ficha inferior
     en vez de comprimir la ventana emergente del escritorio. --}}
@extends('layouts.app')
@section('title', 'Reserva Pistes — encuentra pista y rival')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
@endpush
@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\FrontendAsset::url('ui/home.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\FrontendAsset::url('ui/sport-selection.css') }}">
    <script type="module" src="{{ \App\Support\FrontendAsset::url('ui/club-explorer.js') }}"></script>
@endpush

@section('content')
<section class="rp-home-hero" aria-labelledby="home-heading">
    <div>
        <h1 id="home-heading"><span class="rp-home-heading-desktop">Tu pista, tu partido,<br>a un clic.</span><span class="rp-home-heading-phone">¿Dónde jugamos?</span></h1>
        <p>Clubes reales de Sabadell, Terrassa y Barcelona.</p>
        <span class="rp-mobile-context">Sabadell · Terrassa · Barcelona</span>
    </div>
    {{-- Ilustración decorativa propia; el símbolo de marca está en la cabecera. --}}
    <svg class="rp-home-hero__art" viewBox="0 0 540 250" fill="none" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="home-court-fill" x1="110" y1="75" x2="420" y2="245" gradientUnits="userSpaceOnUse">
                <stop stop-color="#068675"/><stop offset="1" stop-color="#00594e"/>
            </linearGradient>
        </defs>
        <ellipse cx="310" cy="127" rx="270" ry="220" stroke="#dcebe6" stroke-width="2"/>
        <ellipse cx="334" cy="150" rx="218" ry="190" stroke="#a5ccc0" stroke-width="2"/>
        <path d="m153 52 331 0 59 144H91Z" fill="#f4f8f6" stroke="#087567" stroke-width="4" stroke-linejoin="round"/>
        <path d="M196 69h244l37 103H157Z" fill="url(#home-court-fill)"/>
        <path d="M146 70h346M99 173h435" stroke="#087567" stroke-width="4"/>
        <path d="M317 69v103" stroke="#ffb21d" stroke-width="4"/>
        <ellipse cx="317" cy="121" rx="35" ry="22" stroke="#ffb21d" stroke-width="4"/>
    </svg>
</section>

<section class="rp-explorer" aria-label="Encuentra tu club" data-rp-explorer data-drawer-state="map">
    <aside class="rp-sports" aria-labelledby="sport-heading">
        <div class="rp-sports__heading">
            <h2 id="sport-heading">Deportes</h2>

        </div>
        <p class="rp-sports__help">Elige uno o varios. Todos elimina el filtro.</p>
        @include('partials.sport-filter', ['filterPrefix' => 'map-sport', 'filterMode' => 'map'])
        @if($sports->isEmpty())
            <p class="rp-sports__empty">Todavía no hay deportes disponibles.</p>
        @endif
        <p class="rp-sports__count">
            <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 29h26M6 29V10l12-6v25M25 29V14l-7-4M10 12v2m4-4v2m-4 6v2m4-4v2m-4 6v2m4-4v2M22 18v2m0 4v2"/>
            </svg>
            <span id="club-count" role="status" aria-live="polite">Buscando clubes…</span>
        </p>
    </aside>

    <div class="rp-map-frame">
        <div id="rp-club-map" data-clubs-url="{{ route('api.clubs') }}"
             data-tiles-url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
             role="region" aria-label="Mapa de clubes deportivos" aria-busy="true"></div>
        <div class="rp-map-state" data-map-state role="status">
            <h3 data-map-state-title>Preparando el mapa</h3>
            <p data-map-state-text>Buscando clubes de la zona.</p>
            <button type="button" data-map-retry hidden>Reintentar</button>
        </div>
    </div>

    <section class="rp-club-drawer" data-club-drawer aria-label="Clubes de la zona">
        <div class="rp-drawer-heading">
            <button class="rp-drawer-handle" type="button" data-drawer-handle aria-expanded="false" aria-controls="mobile-club-list" aria-label="Abrir la lista de clubes">
                <span class="rp-drawer-grip" aria-hidden="true"></span>
                <span class="rp-drawer-count" data-mobile-count role="status" aria-live="polite">Buscando clubes…</span>
                <span class="rp-drawer-action" data-drawer-action>Ver lista</span>
            </button>
            <button type="button" class="rp-drawer-map" data-drawer-map hidden>Mapa</button>
        </div>
        <div class="rp-selected-club" data-mobile-selection hidden>
            <button type="button" class="rp-selection-close" data-selection-close aria-label="Cerrar la ficha del club">×</button>
            <div data-selected-card aria-live="polite"></div>
        </div>
        <div id="mobile-club-list" class="rp-drawer-list" data-drawer-list hidden>
            <p class="rp-map-message" data-map-message role="status" hidden></p>
            <label class="rp-mobile-sort">Ordenar <select data-club-sort aria-label="Ordenar clubes"><option value="name">Por nombre</option><option value="price">Por precio</option></select></label>
            <div class="rp-mobile-club-list" data-mobile-club-list></div>
        </div>
    </section>
</section>
<noscript>
    <p class="rp-map-message">Activa JavaScript para consultar el mapa y filtrar los clubes.</p>
</noscript>
@endsection
