{{-- Buscador de partidos abiertos. El filtro "solo mi nivel" se resuelve en
     el controlador, porque el nivel depende del deporte de cada partido. --}}
@extends('layouts.app')
@section('title', 'Partidos abiertos — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Partidos abiertos',
        'subtitle' => 'Encuentra gente para jugar en tu club favorito.',
        'image'    => \App\Support\Ui::sportCover('baloncesto-3x3'),
    ])
@endsection

@section('content')

<form class="card filters" method="GET" action="{{ route('matches.index') }}">
    <select name="sport" aria-label="Deporte">
        <option value="">Todos los deportes</option>
        @foreach($sports as $sport)
            <option value="{{ $sport->slug }}" @selected(request('sport') === $sport->slug)>{{ $sport->name }}</option>
        @endforeach
    </select>

    <input type="search" name="club" value="{{ request('club') }}" placeholder="Buscar club…" aria-label="Club">

    <label class="check">
        <input type="checkbox" name="my_level" value="1" @checked(request()->boolean('my_level'))>
        Solo mi nivel
    </label>

    <span class="spacer"></span>
    <button class="btn btn-primary" type="submit">Filtrar</button>
</form>

<div class="stagger">
@forelse($matches as $match)
    @php($apuntado = $match->players->contains('id', auth()->id()))
    @php($libres = $match->freeSlots())
    <article class="card row-card">
        <div class="sport-circle">
            <img src="{{ asset('img/sports/'.$match->court->sport->icon) }}" alt="">
        </div>

        <div class="grow">
            <h3>{{ $match->court->sport->name }}</h3>
            <p class="muted">{{ $match->court->club->name }} · {{ $match->court->name }}</p>
            <p class="tiny" style="margin-top:4px">
                {{ $match->starts_at->translatedFormat('D, j M') }} ·
                {{ $match->starts_at->format('H:i') }}–{{ $match->ends_at->format('H:i') }} ·
                organiza {{ \Illuminate\Support\Str::before($match->user->name, ' ') }}
            </p>
        </div>

        <div style="text-align:center">
            <div class="dots" style="justify-content:center">
                @for($i = 0; $i < $match->court->sport->players_per_match; $i++)
                    <i @class(['on' => $i < $match->players->count()])></i>
                @endfor
            </div>
            <p class="tiny" style="margin-top:7px">
                {{ $match->players->count() }}/{{ $match->court->sport->players_per_match }} jugadores
            </p>
        </div>

        <div class="row-actions">
            @if($match->min_level || $match->max_level)
                <span class="chip">Nivel {{ $match->min_level ?? 1 }}–{{ $match->max_level ?? 5 }}</span>
            @endif

            @if($apuntado)
                <span class="chip">✓ Apuntado</span>
            @elseif($libres > 0)
                <form method="POST" action="{{ route('matches.join', $match) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Unirme</button>
                </form>
            @else
                <span class="chip chip-muted">Completo</span>
            @endif
        </div>
    </article>
@empty
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>No hay partidos con esos filtros</h3>
        <p>Prueba a quitar algún filtro, o abre tú un partido desde la ficha de un club.</p>
        <a class="btn btn-primary" href="{{ route('home') }}">Buscar un club</a>
    </div>
@endforelse
</div>

@endsection
