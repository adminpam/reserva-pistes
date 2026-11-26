{{-- Gestión visual de clubes; se conservan las acciones existentes. --}}
@extends('layouts.app')
@section('title', 'Administración · Clubes — Reserva Pistes')
@section('banner')@include('partials.banner', ['title' => 'Administración', 'subtitle' => 'Gestiona los clubes, deportes y usuarios de la plataforma.'])@endsection
@section('content')
@include('admin._nav')
<form class="rp-filter-bar rp-filter-bar--search" method="GET" action="{{ route('admin.clubs.index') }}"><input type="search" maxlength="150" name="q" value="{{ request('q') }}" placeholder="Buscar club…" aria-label="Buscar club"><button class="btn btn-primary" type="submit">Buscar</button></form>
<div class="rp-list">
@forelse($clubs as $club)
    <article class="rp-panel rp-admin-row">
        <div><h3>{{ $club->name }}</h3><p>{{ $club->address ?: 'Dirección no disponible' }}@if($club->city) · {{ $club->city }}@endif</p><p>{{ $club->courts_count }} {{ $club->courts_count === 1 ? 'pista' : 'pistas' }}</p></div>
        <div class="rp-admin-actions">
            <form method="POST" action="{{ route('admin.clubs.update', $club) }}">@csrf @method('PATCH')<div class="rp-field"><label for="manager-{{ $club->id }}">Gestor</label><select id="manager-{{ $club->id }}" name="manager_id"><option value="">Sin gestor</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected($club->manager_id === $manager->id)>{{ $manager->name }}</option>@endforeach</select></div><button class="btn btn-ghost btn-sm" type="submit">Asignar</button></form>
            <form method="POST" action="{{ route('admin.clubs.destroy', $club) }}" data-confirm="Solo se puede eliminar un club sin pistas. ¿Eliminar este club?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Eliminar</button></form>
        </div>
    </article>
@empty
    @include('partials.empty-state', ['title' => 'No hay clubes con esa búsqueda', 'text' => 'Los clubes publicados aparecerán aquí. Prueba otra búsqueda.'])
@endforelse
</div>
{{ $clubs->links('partials.pagination') }}
@endsection
