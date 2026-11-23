{{-- Disponibilidad real de una hora por pista. Las horas ocupadas o pasadas no
     pueden elegirse y se conserva el contrato de BookingController. --}}
@extends('layouts.app')
@section('title', $club->name.' — Reserva Pistes')
@push('styles')<script type="module" src="{{ asset('ui/availability.js') }}"></script>@endpush
@section('banner')<div class="rp-club-cover" aria-hidden="true"></div>@endsection
@section('content')
<section class="rp-panel rp-club-summary" aria-labelledby="club-title">
    <div class="rp-club-summary__info">
        <h1 id="club-title">{{ $club->name }}</h1>
        <p class="rp-club-address">@include('partials.ui-icon', ['name' => 'pin']) <span>{{ trim(($club->address ?? '').($club->city ? ', '.$club->city : ''), ', ') ?: 'Dirección no disponible' }}</span></p>
        <div class="rp-tag-list">@foreach($club->sportList()->filter() as $sport)<span class="rp-sport-tag">@include('partials.sport-icon', ['slug' => $sport->slug]) {{ $sport->name }}</span>@endforeach</div>
        @if($club->description)<p class="rp-small">{{ $club->description }}</p>@endif
    </div>
    @if($hasOutdoor)<div id="weather" class="weather" data-lat="{{ $club->lat }}" data-lng="{{ $club->lng }}" hidden></div>@endif
</section>
<form class="rp-filter-bar rp-filter-bar--date" method="GET" action="{{ route('clubs.show', $club) }}">
    <div class="rp-field"><label for="booking-date">Día de la reserva</label><input id="booking-date" type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}" required></div>
    <button class="btn btn-ghost" type="submit">@include('partials.ui-icon', ['name' => 'calendar']) Ver disponibilidad</button>
</form>
<p class="rp-notice">@include('partials.ui-icon', ['name' => 'info']) <span>Las pistas, los precios y las reservas son de demostración. No se realiza ningún cobro real.</span></p>
<div class="rp-list" data-availability-url="{{ route('clubs.availability', ['club' => $club, 'date' => $date]) }}" data-availability-date="{{ $date }}">
<p class="rp-small" data-availability-status role="status" aria-live="polite">Las horas se actualizan al volver a esta pestaña.</p>
@forelse($club->courts as $court)
    @php
        $courtAvailability = collect($availability['courts'])->firstWhere('id', $court->id);
        $slots = collect($courtAvailability['hours'] ?? []);
        $hours = $slots->pluck('hour')->all();
        $available = $slots->where('available', true)->pluck('hour')->values();
        $selectedHour = (string) old('court_id') === (string) $court->id ? old('hour', $available->first()) : $available->first();
    @endphp
    <section class="rp-panel rp-court" aria-labelledby="court-{{ $court->id }}-title">
        @include('partials.sport-icon', ['slug' => $court->sport->slug])
        <div>
            <h3 id="court-{{ $court->id }}-title">{{ $court->name }} — {{ $court->sport->name }}</h3>
            <p>{{ \App\Support\Ui::euros($court->price_per_hour) }}/hora · {{ $court->is_outdoor ? 'Exterior' : 'Interior' }} · {{ sprintf('%02d:00', $court->open_hour) }}–{{ sprintf('%02d:00', $court->close_hour) }}</p>
        </div>
        @auth
            <form class="rp-booking-form" data-booking-form method="POST" action="{{ route('bookings.store') }}">
                @csrf<input type="hidden" name="court_id" value="{{ $court->id }}"><input type="hidden" name="date" value="{{ $date }}">
                <div>
                    <div class="rp-slot-grid" role="group" aria-label="Horas de {{ $court->name }}">
                    @foreach($hours as $hour)
                        @php($unavailable = !$available->contains($hour))
                        <div class="rp-slot-choice">
                            <input type="radio" name="hour" id="court-{{ $court->id }}-hour-{{ $hour }}" value="{{ $hour }}" @disabled($unavailable) @checked(!$unavailable && (string) $selectedHour === (string) $hour) required>
                            <label for="court-{{ $court->id }}-hour-{{ $hour }}">{{ sprintf('%02d:00', $hour) }}</label>
                        </div>
                    @endforeach
                    </div>
                    <div class="rp-booking-options">
                        <label class="rp-toggle"><input type="checkbox" class="open-toggle" name="is_open" value="1"> Partido abierto @include('partials.ui-icon', ['name' => 'info'])</label>
                        <div class="open-options rp-open-levels" hidden>
                            <div class="rp-field"><label for="min-{{ $court->id }}">Nivel mínimo</label><select id="min-{{ $court->id }}" name="min_level"><option value="">Sin mínimo</option>@for($level = 1; $level <= 5; $level++)<option value="{{ $level }}">{{ $level }}</option>@endfor</select></div>
                            <div class="rp-field"><label for="max-{{ $court->id }}">Nivel máximo</label><select id="max-{{ $court->id }}" name="max_level"><option value="">Sin máximo</option>@for($level = 1; $level <= 5; $level++)<option value="{{ $level }}">{{ $level }}</option>@endfor</select></div>
                        </div>
                    </div>
                    <div class="rp-slot-summary" data-slot-summary aria-live="polite">Elige una hora disponible.</div>
                    <p class="rp-small" data-availability-empty @if($available->isNotEmpty()) hidden @endif>No quedan horas disponibles para este día.</p>
                </div>
                <button class="btn btn-primary" type="submit" @disabled($available->isEmpty())>Reservar</button>
            </form>
        @else
            <div class="rp-court-guest"><a class="btn btn-primary" href="{{ route('login') }}">Entra para reservar</a></div>
        @endauth
    </section>
@empty
    @include('partials.empty-state', ['title' => 'Este club todavía no tiene pistas', 'text' => 'Su gestor aún no ha publicado pistas disponibles.', 'href' => route('home'), 'label' => 'Ver otros clubes'])
@endforelse
</div>
@endsection
