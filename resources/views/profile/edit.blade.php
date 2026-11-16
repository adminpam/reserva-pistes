{{-- Perfil: nombre + nivel 1-5 por deporte. El nivel vacio significa
     que el jugador no practica ese deporte. --}}
@extends('layouts.app')
@section('title', 'Mi perfil — ReservaPistes')

@section('content')
<div class="auth-card card">
    <h1>Mi perfil</h1>
    <p class="muted">Tu nivel (1 = principiante, 5 = avanzado) se usa para los partidos con rango de nivel.</p>

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PATCH')

        <label for="name">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="100">

        <h2 class="h-small">Mis niveles</h2>
        @foreach($sports as $sport)
            <label class="level-row">
                <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="" width="22" height="22">
                <span>{{ $sport->name }}</span>
                <select name="levels[{{ $sport->id }}]">
                    <option value="">No juego</option>
                    @for($l = 1; $l <= 5; $l++)
                        <option value="{{ $l }}" @selected($user->levelFor($sport->id) === $l)>Nivel {{ $l }}</option>
                    @endfor
                </select>
            </label>
        @endforeach

        <button class="btn btn-primary btn-block" type="submit">Guardar cambios</button>
    </form>
</div>
@endsection
