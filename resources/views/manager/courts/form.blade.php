{{-- Formulario único de alta/edición con los nombres de campo del controlador. --}}
@extends('layouts.app')
@section('title', ($court ? 'Editar pista' : 'Nueva pista').' — Reserva Pistes')
@section('banner')@include('partials.banner', ['title' => $court ? 'Editar pista' : 'Nueva pista', 'subtitle' => 'Configura el deporte, el precio y el horario.'])@endsection
@section('content')
<form class="rp-form-narrow rp-panel" method="POST" action="{{ $court ? route('manager.courts.update', $court) : route('manager.courts.store') }}">
    @csrf @if($court) @method('PATCH') @endif
    <h2>Datos de la pista</h2>
    <div class="rp-form-grid">
        <div class="rp-field"><label for="club_id">Club</label><select id="club_id" name="club_id" required>@foreach($clubs as $club)<option value="{{ $club->id }}" @selected(old('club_id', $court?->club_id) == $club->id)>{{ $club->name }}</option>@endforeach</select></div>
        <div class="rp-field"><label for="name">Nombre de la pista</label><input id="name" name="name" value="{{ old('name', $court?->name) }}" required maxlength="100" placeholder="Pista 1"></div>
        <fieldset class="rp-level-field rp-field--full"><legend class="rp-form-legend">Deporte</legend><div class="rp-choice-grid">@foreach($sports as $sport)<label><input type="radio" name="sport_id" value="{{ $sport->id }}" @checked(old('sport_id', $court?->sport_id) == $sport->id) required>@include('partials.sport-icon', ['slug' => $sport->slug]) {{ $sport->name }}</label>@endforeach</div></fieldset>
        <div class="rp-field"><label for="price_euros">Precio por hora (€)</label><input id="price_euros" name="price_euros" type="number" min="0" max="500" step="0.01" value="{{ old('price_euros', $court ? $court->price_per_hour / 100 : '') }}" required></div>
        <div class="rp-field"><label for="open_hour">Hora de apertura (0–23)</label><input id="open_hour" name="open_hour" type="number" min="0" max="23" value="{{ old('open_hour', $court?->open_hour ?? 8) }}" required></div>
        <div class="rp-field"><label for="close_hour">Hora de cierre (1–24)</label><input id="close_hour" name="close_hour" type="number" min="1" max="24" value="{{ old('close_hour', $court?->close_hour ?? 22) }}" required></div>
        <label class="rp-toggle"><input type="checkbox" name="is_outdoor" value="1" @checked(old('is_outdoor', $court?->is_outdoor))> Pista exterior</label>
    </div>
    @if($clubs->isEmpty() || $sports->isEmpty())<p class="rp-notice">Debe haber un club asignado y al menos un deporte para crear una pista.</p>@endif
    <div class="rp-form-footer"><a class="rp-muted" href="{{ route('manager.courts.index') }}">Cancelar</a><button class="btn btn-primary" type="submit" @disabled($clubs->isEmpty() || $sports->isEmpty())>Guardar pista</button></div>
</form>
@endsection
