{{-- Administración de deportes: jugadores por partido, alta y baja. --}}
@extends('layouts.app')
@section('title', 'Administración · Deportes — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Administración',
        'subtitle' => 'Clubes, deportes y usuarios de la plataforma.',
        'image'    => \App\Support\Ui::sportCover('voleibol'),
    ])
@endsection

@section('content')
@include('admin._nav')

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px" class="stagger">
@foreach($sports as $sport)
    <article class="card" style="text-align:center">
        <div class="sport-circle" style="width:72px;height:72px;margin:4px auto 16px">
            <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="" style="width:40px;height:40px">
        </div>

        <h3 style="font-size:1.25rem">{{ $sport->name }}</h3>
        <p class="tiny" style="margin-top:4px">
            {{ $sport->courts_count }} {{ $sport->courts_count === 1 ? 'pista' : 'pistas' }}
        </p>

        <form method="POST" action="{{ route('admin.sports.update', $sport) }}"
              class="inline" style="justify-content:center;margin-top:18px">
            @csrf @method('PATCH')
            <label class="tiny" for="ppm{{ $sport->id }}" style="font-weight:700">Jugadores</label>
            <input id="ppm{{ $sport->id }}" type="number" name="players_per_match" min="2" max="30"
                   value="{{ $sport->players_per_match }}" style="width:86px;height:42px;text-align:center">
            <button class="btn btn-ghost btn-sm" type="submit">Guardar</button>
        </form>

        <form method="POST" action="{{ route('admin.sports.destroy', $sport) }}" style="margin-top:14px"
              onsubmit="return confirm('¿Eliminar este deporte?')">
            @csrf @method('DELETE')
            <button type="submit" class="tiny"
                    style="background:none;border:none;color:var(--clay);cursor:pointer;font-weight:600">
                Eliminar
            </button>
        </form>
    </article>
@endforeach
</div>

<div class="card mt-xl" style="border-style:dashed">
    <h2 class="section-title">Nuevo deporte</h2>
    <form method="POST" action="{{ route('admin.sports.store') }}" class="inline">
        @csrf
        <input type="text" name="name" placeholder="Nombre del deporte" required maxlength="60"
               style="flex:1;min-width:200px" aria-label="Nombre">
        <input type="number" name="players_per_match" placeholder="Jugadores" min="2" max="30" required
               style="width:150px" aria-label="Jugadores por partido">
        <button class="btn btn-primary" type="submit">Crear deporte</button>
    </form>
</div>
@endsection
