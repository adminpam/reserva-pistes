{{-- Búsqueda y acciones reales del controlador de partidos existente. --}}
@extends('layouts.app')
@section('title', 'Partidos abiertos — Reserva Pistes')
@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\FrontendAsset::url('ui/sport-selection.css') }}">
    <script type="module" src="{{ \App\Support\FrontendAsset::url('ui/sport-selection.js') }}"></script>
@endpush
@section('banner')
    @include('partials.banner', ['title' => 'Partidos abiertos', 'subtitle' => 'Encuentra gente para jugar en tu club favorito.'])
@endsection
@section('content')
<form class="rp-filter-bar rp-filter-bar--sports" method="GET" action="{{ route('matches.index') }}">
    <fieldset class="rp-match-sports">
        <legend>Deportes · elige uno o varios</legend>
        @include('partials.sport-filter', ['filterPrefix' => 'match-sport', 'filterMode' => 'submit'])
        <noscript><p class="rp-small">Desmarca todos los deportes para buscar sin filtro.</p></noscript>
    </fieldset>
    <input type="search" name="club" value="{{ request('club') }}" placeholder="Buscar club…" aria-label="Buscar club">
    <label class="rp-toggle"><input type="checkbox" name="my_level" value="1" @checked(request()->boolean('my_level'))> Solo mi nivel</label>
    <button class="btn btn-primary" type="submit">@include('partials.ui-icon', ['name' => 'filter']) Filtrar</button>
</form>
<div class="rp-list">
@forelse($matches as $match)
    @php($apuntado = $match->players->contains('id', auth()->id()))
    @php($capacity = (int) $match->court->sport->players_per_match)
    <article class="rp-record rp-match-offer">
        <span class="rp-sport-disc">@include('partials.sport-icon', ['slug' => $match->court->sport->slug])</span>
        <div>
            <h3>{{ $match->court->sport->name }}</h3>
            <p class="rp-record__sub">{{ $match->court->club->name }}</p>
            <div class="rp-record__meta">
                <span>@include('partials.ui-icon', ['name' => 'calendar']) {{ $match->starts_at->translatedFormat('D, j M Y') }}</span>
                <span>@include('partials.ui-icon', ['name' => 'clock']) {{ $match->starts_at->format('H:i') }}–{{ $match->ends_at->format('H:i') }}</span>
                <span>@include('partials.ui-icon', ['name' => 'user']) {{ \Illuminate\Support\Str::before($match->user->name, ' ') }}</span>
            </div>
        </div>
        <div class="rp-capacity">
            <div class="rp-capacity__dots" aria-hidden="true">@for($i = 0; $i < min(12, $capacity); $i++)<i @class(['is-filled' => $i < $match->players->count()])></i>@endfor</div>
            <p class="rp-player-count">{{ $match->players->count() }}/{{ $capacity }} jugadores</p>
        </div>
        <div class="rp-match-level"><span class="rp-badge">{{ $match->min_level || $match->max_level ? 'Nivel '.($match->min_level ?? 1).'–'.($match->max_level ?? 5) : 'Todos los niveles' }}</span></div>
        <div class="rp-match-join">
            @if($apuntado)<span class="rp-badge">@include('partials.ui-icon', ['name' => 'check']) Apuntado</span>
            @elseif($match->freeSlots() > 0)<form method="POST" action="{{ route('matches.join', $match) }}">@csrf<button class="btn btn-primary" type="submit">Unirme <span aria-hidden="true">›</span></button></form>
            @else<span class="rp-badge rp-badge--muted">Completo</span>@endif
        </div>
    </article>
@empty
    @include('partials.empty-state', ['title' => 'No hay partidos con esos filtros', 'text' => 'Prueba otro deporte o abre un partido desde la ficha de un club.', 'href' => route('home'), 'label' => 'Buscar un club'])
@endforelse
</div>
@endsection
