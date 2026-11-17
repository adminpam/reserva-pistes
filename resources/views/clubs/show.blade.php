{{-- Ficha de club: cabecera con la foto del deporte principal, el tiempo si
     hay pista exterior, y una tarjeta por pista con sus horas libres. --}}
@extends('layouts.app')
@section('title', $club->name.' — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => $club->name,
        'subtitle' => trim($club->address.($club->city ? ', '.$club->city : ''), ', ') ?: 'Dirección pendiente',
        'image'    => \App\Support\Ui::sportCover($club->courts->first()?->sport?->slug),
    ])
@endsection

@section('content')

<div class="card row-card" style="margin-bottom:28px">
    <div class="grow">
        @if($club->description)
            <p class="muted" style="margin-bottom:12px">{{ $club->description }}</p>
        @endif
        <div class="chips">
            @foreach($club->sportList() as $sport)
                <span class="chip">
                    <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="">
                    {{ $sport->name }}
                </span>
            @endforeach
        </div>
    </div>

    @if($hasOutdoor)
        {{-- app.js rellena este bloque con la previsión de Open-Meteo --}}
        <div id="weather" class="weather" data-lat="{{ $club->lat }}" data-lng="{{ $club->lng }}" hidden></div>
    @endif
</div>

<form class="inline" method="GET" action="{{ route('clubs.show', $club) }}" style="margin-bottom:22px">
    <div class="field" style="margin:0">
        <label for="date">Día de la reserva</label>
        <input type="date" id="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}">
    </div>
    <button class="btn btn-ghost" type="submit" style="margin-top:26px">Ver disponibilidad</button>
</form>

<div class="notice" style="margin-bottom:32px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
    </svg>
    <span>Las reservas de este proyecto son simuladas: no se cobra ningún pago real.</span>
</div>

<h2 class="section-title">Pistas disponibles</h2>

<div class="stagger">
@forelse($club->courts as $court)
    <section class="card row-card">
        <img class="thumb" src="{{ \App\Support\Ui::sportThumb($court->sport->slug) }}" alt="" loading="lazy">

        <div class="grow">
            <h3>{{ $court->name }}</h3>
            <p class="muted">
                {{ $court->sport->name }} · {{ $court->priceEuros() }} €/hora ·
                {{ $court->is_outdoor ? 'Exterior' : 'Interior' }} ·
                {{ $court->open_hour }}:00–{{ $court->close_hour }}:00
            </p>
        </div>

        @auth
            <form method="POST" action="{{ route('bookings.store') }}" class="inline" style="gap:20px">
                @csrf
                <input type="hidden" name="court_id" value="{{ $court->id }}">
                <input type="hidden" name="date" value="{{ $date }}">

                <div>
                    <div class="slots">
                        @foreach(range($court->open_hour, $court->close_hour - 1) as $h)
                            @php($ocupada = in_array($h, $busy[$court->id] ?? []))
                            <input type="radio" name="hour" id="h{{ $court->id }}-{{ $h }}"
                                   value="{{ $h }}" @disabled($ocupada) @checked(! $ocupada && $loop->first)>
                            <label for="h{{ $court->id }}-{{ $h }}">{{ sprintf('%02d:00', $h) }}</label>
                        @endforeach
                    </div>

                    <label class="check" style="margin-top:14px">
                        <input type="checkbox" name="is_open" value="1" class="open-toggle">
                        Abrir como partido y buscar gente
                    </label>

                    <div class="open-options inline" style="margin-top:12px" hidden>
                        <label class="inline" style="gap:8px;font-size:.875rem;font-weight:600">
                            Nivel mínimo
                            <select name="min_level" style="width:auto;height:40px">
                                <option value="">—</option>
                                @for($l = 1; $l <= 5; $l++)<option value="{{ $l }}">{{ $l }}</option>@endfor
                            </select>
                        </label>
                        <label class="inline" style="gap:8px;font-size:.875rem;font-weight:600">
                            máximo
                            <select name="max_level" style="width:auto;height:40px">
                                <option value="">—</option>
                                @for($l = 1; $l <= 5; $l++)<option value="{{ $l }}">{{ $l }}</option>@endfor
                            </select>
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">Reservar</button>
            </form>
        @else
            <div class="row-actions">
                <a class="btn btn-ghost" href="{{ route('login') }}">Entra para reservar</a>
            </div>
        @endauth
    </section>
@empty
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>Este club todavía no tiene pistas</h3>
        <p>Su gestor aún no las ha dado de alta.</p>
        <a class="btn btn-primary" href="{{ route('home') }}">Ver otros clubes</a>
    </div>
@endforelse
</div>

@endsection
