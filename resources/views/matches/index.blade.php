{{-- Поиск открытых матчей: фильтры (спорт, мой уровень, клуб) + карточки
     с местами и кнопкой записи. --}}
@extends('layouts.app')
@section('title', 'Partidos abiertos — ReservaPistes')

@section('content')
<h1>Partidos abiertos</h1>

<form class="filters" method="GET" action="{{ route('matches.index') }}">
    <select name="sport">
        <option value="">Todos los deportes</option>
        @foreach($sports as $sport)
            <option value="{{ $sport->slug }}" @selected(request('sport') === $sport->slug)>{{ $sport->name }}</option>
        @endforeach
    </select>
    <input type="text" name="club" value="{{ request('club') }}" placeholder="Nombre del club…">
    <label class="check">
        <input type="checkbox" name="my_level" value="1" @checked(request()->boolean('my_level'))> Solo mi nivel
    </label>
    <button class="btn btn-ghost" type="submit">Filtrar</button>
</form>

@forelse($matches as $match)
    <div class="card row-card">
        <div>
            <strong>{{ $match->court->sport->name }}</strong> en {{ $match->court->club->name }}
            <p class="muted">
                {{ $match->court->name }} · {{ $match->starts_at->format('d/m/Y H:i') }} ·
                Organiza: {{ $match->user->name }}
                @if($match->min_level || $match->max_level)
                    · Nivel {{ $match->min_level ?? 1 }}–{{ $match->max_level ?? 5 }}
                @endif
            </p>
        </div>
        <div class="row-actions">
            <span class="badge">{{ $match->players->count() }}/{{ $match->court->sport->players_per_match }} jugadores</span>
            @if($match->players->contains('id', auth()->id()))
                <span class="badge badge-ok">Apuntado</span>
            @elseif($match->freeSlots() > 0)
                <form method="POST" action="{{ route('matches.join', $match) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Unirme</button>
                </form>
            @else
                <span class="badge badge-muted">Completo</span>
            @endif
        </div>
    </div>
@empty
    <div class="empty-state">
        <img src="{{ asset('img/empty.svg') }}" alt="" width="120">
        <p>No hay partidos abiertos con esos filtros. ¡Crea uno desde la ficha de un club!</p>
    </div>
@endforelse
@endsection
