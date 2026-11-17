{{-- Alta y edición de pista. El precio se escribe en euros y el controlador
     lo guarda en céntimos. --}}
@extends('layouts.app')
@section('title', ($court ? 'Editar' : 'Nueva').' pista — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => $court ? 'Editar pista' : 'Nueva pista',
        'subtitle' => $court ? $court->club->name : 'Da de alta una pista en tu club.',
        'image'    => \App\Support\Ui::sportCover($court?->sport?->slug),
    ])
@endsection

@section('content')
<form method="POST" style="max-width:760px;margin:0 auto"
      action="{{ $court ? route('manager.courts.update', $court) : route('manager.courts.store') }}">
    @csrf
    @if($court) @method('PATCH') @endif

    <div class="card" style="padding:36px">

        <h2 class="section-title">Datos básicos</h2>

        <div class="field">
            <label for="club_id">Club</label>
            <select id="club_id" name="club_id" required>
                @foreach($clubs as $club)
                    <option value="{{ $club->id }}" @selected(old('club_id', $court?->club_id) == $club->id)>
                        {{ $club->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="name">Nombre de la pista</label>
            <input id="name" name="name" type="text" value="{{ old('name', $court?->name) }}"
                   placeholder="Pista 1" required maxlength="100">
        </div>

        <h2 class="section-title" style="margin-top:32px">Deporte</h2>
        <div class="sport-list" style="flex-direction:row;flex-wrap:wrap;gap:10px">
            @foreach($sports as $sport)
                <input type="radio" name="sport_id" id="sp{{ $sport->id }}" value="{{ $sport->id }}"
                       @checked(old('sport_id', $court?->sport_id) == $sport->id) required>
                <label for="sp{{ $sport->id }}" style="border:1.5px solid var(--line-strong);border-radius:999px">
                    <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="">
                    {{ $sport->name }}
                </label>
            @endforeach
        </div>

        <h2 class="section-title" style="margin-top:32px">Precio y horario</h2>

        <div class="two-cols">
            <div class="field">
                <label for="price_euros">Precio por hora (€)</label>
                <input id="price_euros" name="price_euros" type="number" step="0.50" min="0" max="500"
                       value="{{ old('price_euros', $court ? $court->price_per_hour / 100 : '') }}" required>
            </div>
            <div class="two-cols">
                <div class="field">
                    <label for="open_hour">Abre a las</label>
                    <input id="open_hour" name="open_hour" type="number" min="0" max="23"
                           value="{{ old('open_hour', $court?->open_hour ?? 8) }}" required>
                </div>
                <div class="field">
                    <label for="close_hour">Cierra a las</label>
                    <input id="close_hour" name="close_hour" type="number" min="1" max="24"
                           value="{{ old('close_hour', $court?->close_hour ?? 22) }}" required>
                </div>
            </div>
        </div>

        <h2 class="section-title" style="margin-top:32px">Opciones</h2>

        <label class="switch">
            <input type="checkbox" name="is_outdoor" value="1" @checked(old('is_outdoor', $court?->is_outdoor))>
            <span class="track"></span>
            <span>
                <strong>Pista exterior</strong>
                <span class="tiny" style="display:block">Mostraremos el tiempo actual en la ficha del club.</span>
            </span>
        </label>

        <div class="inline" style="justify-content:space-between;margin-top:32px;padding-top:24px;border-top:1px solid var(--line)">
            <a class="muted" href="{{ route('manager.courts.index') }}">Cancelar</a>
            <button class="btn btn-primary" type="submit">Guardar pista</button>
        </div>
    </div>
</form>
@endsection
