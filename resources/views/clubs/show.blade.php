{{-- Ficha del club: datos, tiempo (si hay pista exterior, via Open-Meteo
     desde el navegador), y una tarjeta por pista con el selector de hora.
     Las horas ocupadas llegan ya calculadas desde el controlador ($busy). --}}
@extends('layouts.app')
@section('title', $club->name.' — ReservaPistes')

@section('content')
<article class="club-page">
    <header class="club-head">
        <img class="club-cover" src="{{ asset('img/club-cover.svg') }}" alt="">
        <div>
            <h1>{{ $club->name }}</h1>
            <p class="muted">
                {{ $club->address ?: 'Dirección pendiente' }}@if($club->city), {{ $club->city }}@endif
            </p>
            @if($club->description)
                <p>{{ $club->description }}</p>
            @endif
            <div class="chips">
                @foreach($club->sportList() as $sport)
                    <span class="chip">{{ $sport->name }}</span>
                @endforeach
            </div>
        </div>
        @if($hasOutdoor)
            {{-- app.js rellena este bloque con la prevision de Open-Meteo (sin clave). --}}
            <div id="weather" class="weather-card" data-lat="{{ $club->lat }}" data-lng="{{ $club->lng }}">
                <span class="muted">Cargando el tiempo…</span>
            </div>
        @endif
    </header>

    <form class="date-picker" method="GET" action="{{ route('clubs.show', $club) }}">
        <label for="date">Día de la reserva:</label>
        <input type="date" id="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}">
        <button class="btn btn-ghost" type="submit">Ver disponibilidad</button>
    </form>

    <p class="sim-note">ℹ️ Las reservas de este proyecto son simuladas: no se cobra ningún pago real.</p>

    @forelse($club->courts as $court)
        <section class="card court-card">
            <div class="court-info">
                <img src="{{ asset('img/sports/'.$court->sport->icon) }}" alt="" width="30" height="30">
                <div>
                    <h2>{{ $court->name }}</h2>
                    <p class="muted">
                        {{ $court->sport->name }} · {{ $court->priceEuros() }} €/hora ·
                        {{ $court->is_outdoor ? 'Exterior' : 'Interior' }} ·
                        {{ $court->open_hour }}:00–{{ $court->close_hour }}:00
                    </p>
                </div>
            </div>

            @auth
                <form class="booking-form" method="POST" action="{{ route('bookings.store') }}">
                    @csrf
                    <input type="hidden" name="court_id" value="{{ $court->id }}">
                    <input type="hidden" name="date" value="{{ $date }}">

                    <label for="hour-{{ $court->id }}">Hora:</label>
                    <select id="hour-{{ $court->id }}" name="hour" required>
                        @for($h = $court->open_hour; $h < $court->close_hour; $h++)
                            <option value="{{ $h }}" @disabled(in_array($h, $busy[$court->id] ?? []))>
                                {{ sprintf('%02d:00', $h) }}{{ in_array($h, $busy[$court->id] ?? []) ? ' — ocupada' : '' }}
                            </option>
                        @endfor
                    </select>

                    {{-- Opciones de partido abierto: se muestran al marcar el checkbox (app.js). --}}
                    <label class="check">
                        <input type="checkbox" name="is_open" value="1" class="open-toggle"> Partido abierto
                    </label>
                    <div class="open-options" hidden>
                        <label>Nivel mín.
                            <select name="min_level">
                                <option value="">—</option>
                                @for($l = 1; $l <= 5; $l++)<option value="{{ $l }}">{{ $l }}</option>@endfor
                            </select>
                        </label>
                        <label>Nivel máx.
                            <select name="max_level">
                                <option value="">—</option>
                                @for($l = 1; $l <= 5; $l++)<option value="{{ $l }}">{{ $l }}</option>@endfor
                            </select>
                        </label>
                    </div>

                    <button class="btn btn-primary" type="submit">Reservar</button>
                </form>
            @else
                <p class="muted"><a href="{{ route('login') }}">Entra</a> para reservar esta pista.</p>
            @endauth
        </section>
    @empty
        <div class="empty-state">
            <img src="{{ asset('img/empty.svg') }}" alt="" width="120">
            <p>Este club todavía no tiene pistas registradas.</p>
        </div>
    @endforelse
</article>
@endsection
