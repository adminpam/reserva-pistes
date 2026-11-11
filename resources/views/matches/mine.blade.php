{{-- «Мои матчи»: созданные мной и те, куда я записан. Создатель отменяет
     матч целиком, участник — просто выходит. --}}
@extends('layouts.app')
@section('title', 'Mis partidos — ReservaPistes')

@section('content')
<h1>Mis partidos</h1>

@forelse($matches as $match)
    <div class="card row-card @if($match->status === 'cancelled') is-cancelled @endif">
        <div>
            <strong>{{ $match->court->sport->name }}</strong> en {{ $match->court->club->name }}
            <p class="muted">
                {{ $match->starts_at->format('d/m/Y H:i') }} ·
                {{ $match->players->count() }}/{{ $match->court->sport->players_per_match }} jugadores ·
                {{ $match->user_id === auth()->id() ? 'Organizador: tú' : 'Organiza: '.$match->user->name }}
            </p>
        </div>
        <div class="row-actions">
            @if($match->status === 'cancelled')
                <span class="badge badge-muted">Cancelado</span>
            @elseif($match->starts_at->isFuture())
                <form method="POST" action="{{ route('matches.leave', $match) }}"
                      onsubmit="return confirm('{{ $match->user_id === auth()->id() ? '¿Cancelar el partido para todos?' : '¿Salir del partido?' }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">
                        {{ $match->user_id === auth()->id() ? 'Cancelar partido' : 'Salir' }}
                    </button>
                </form>
            @else
                <span class="badge">Jugado</span>
            @endif
        </div>
    </div>
@empty
    <div class="empty-state">
        <img src="{{ asset('img/empty.svg') }}" alt="" width="120">
        <p>No estás en ningún partido. <a href="{{ route('matches.index') }}">Busca uno abierto</a>.</p>
    </div>
@endforelse
@endsection
