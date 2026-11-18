{{-- Catálogo y capacidad: los valores siempre proceden de la base de datos. --}}
@extends('layouts.app')
@section('title', 'Administración · Deportes — Reserva Pistes')
@section('banner')@include('partials.banner', ['title' => 'Administración', 'subtitle' => 'Gestiona los clubes, deportes y usuarios de la plataforma.'])@endsection
@section('content')
@include('admin._nav')
<div class="rp-sport-admin-grid">
@forelse($sports as $sport)
    <article class="rp-panel rp-sport-admin">
        <span class="rp-sport-disc">@include('partials.sport-icon', ['slug' => $sport->slug])</span><h3>{{ $sport->name }}</h3><p class="rp-small">{{ $sport->courts_count }} pistas asociadas</p>
        <form method="POST" action="{{ route('admin.sports.update', $sport) }}">@csrf @method('PATCH')<div class="rp-field"><label for="capacity-{{ $sport->id }}">Jugadores</label><input id="capacity-{{ $sport->id }}" type="number" name="players_per_match" min="2" max="30" value="{{ $sport->players_per_match }}" required></div><button class="btn btn-ghost btn-sm" type="submit">Guardar</button></form>
        @if($sport->courts_count === 0)<form method="POST" action="{{ route('admin.sports.destroy', $sport) }}" data-confirm="¿Eliminar este deporte?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Eliminar</button></form>@else<span class="rp-small">Tiene pistas asociadas</span>@endif
    </article>
@empty
    <p class="rp-muted">Todavía no hay deportes en el catálogo.</p>
@endforelse
</div>
<section class="rp-panel rp-add-form"><h2>Nuevo deporte</h2><form class="rp-form-grid" method="POST" action="{{ route('admin.sports.store') }}">@csrf<div class="rp-field"><label for="sport-name">Nombre</label><input id="sport-name" name="name" maxlength="60" value="{{ old('name') }}" required></div><div class="rp-field"><label for="sport-capacity">Jugadores por partido</label><input id="sport-capacity" name="players_per_match" type="number" min="2" max="30" required></div><button class="btn btn-primary" type="submit">Crear deporte</button></form></section>
@endsection
