{{-- Formulario compartido de crear/editar pista del gestor. --}}
@extends('layouts.app')
@section('title', ($court ? 'Editar' : 'Nueva').' pista — ReservaPistes')

@section('content')
<div class="auth-card card">
    <h1>{{ $court ? 'Editar pista' : 'Nueva pista' }}</h1>

    <form method="POST"
          action="{{ $court ? route('manager.courts.update', $court) : route('manager.courts.store') }}">
        @csrf
        @if($court) @method('PATCH') @endif

        <label for="club_id">Club</label>
        <select id="club_id" name="club_id" required>
            @foreach($clubs as $club)
                <option value="{{ $club->id }}" @selected(old('club_id', $court?->club_id) == $club->id)>{{ $club->name }}</option>
            @endforeach
        </select>

        <label for="sport_id">Deporte</label>
        <select id="sport_id" name="sport_id" required>
            @foreach($sports as $sport)
                <option value="{{ $sport->id }}" @selected(old('sport_id', $court?->sport_id) == $sport->id)>{{ $sport->name }}</option>
            @endforeach
        </select>

        <label for="name">Nombre de la pista</label>
        <input id="name" name="name" type="text" value="{{ old('name', $court?->name) }}" required maxlength="100">

        <label for="price_euros">Precio por hora (€)</label>
        <input id="price_euros" name="price_euros" type="number" step="0.50" min="0" max="500"
               value="{{ old('price_euros', $court ? $court->price_per_hour / 100 : '') }}" required>

        <div class="two-cols">
            <label>Abre a las
                <input name="open_hour" type="number" min="0" max="23" value="{{ old('open_hour', $court?->open_hour ?? 8) }}" required>
            </label>
            <label>Cierra a las
                <input name="close_hour" type="number" min="1" max="24" value="{{ old('close_hour', $court?->close_hour ?? 22) }}" required>
            </label>
        </div>

        <label class="check">
            <input type="checkbox" name="is_outdoor" value="1" @checked(old('is_outdoor', $court?->is_outdoor))>
            Pista exterior (se mostrará el tiempo en la ficha del club)
        </label>

        <button class="btn btn-primary btn-block" type="submit">Guardar</button>
    </form>
</div>
@endsection
