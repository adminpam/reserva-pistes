{{-- Los niveles siguen siendo 1–5; «No juego» permite borrar un nivel guardado. --}}
@extends('layouts.app')
@section('title', 'Mi perfil — Reserva Pistes')
@section('banner')
    @include('partials.banner', ['title' => 'Mi perfil', 'subtitle' => 'Gestiona tus datos y tus niveles de juego.'])
@endsection
@section('content')
<form method="POST" action="{{ route('profile.update') }}">
    @csrf @method('PATCH')
    <div class="rp-profile">
        <aside class="rp-panel rp-profile-summary">
            <div class="rp-profile-avatar" aria-hidden="true">{{ \App\Support\Ui::initials($user->name) }}</div>
            <h2>{{ $user->name }}</h2>
            <p class="rp-profile-email">{{ $user->email }}</p>
            <p class="rp-profile-since">@include('partials.ui-icon', ['name' => 'calendar']) <span>Miembro desde {{ $user->created_at->translatedFormat('F \d\e Y') }}</span></p>
            @if($user->isManager() || $user->isAdmin())<span class="rp-badge">{{ $user->isAdmin() ? 'Administrador' : 'Gestor de club' }}</span>@endif
        </aside>
        <section class="rp-panel rp-profile-editor" aria-labelledby="levels-heading">
            <h2 id="levels-heading">Mis niveles</h2>
            <p class="rp-small">1: principiante · 5: avanzado. El nivel se utiliza en los partidos abiertos.</p>
            <div class="rp-levels">
            @forelse($sports as $sport)
                @php($actual = old('levels.'.$sport->id, $user->levelFor($sport->id)))
                <fieldset class="rp-level-field">
                    <legend class="rp-sr">Nivel de {{ $sport->name }}</legend>
                    <div class="rp-level-row">
                        <span class="rp-level-sport" aria-hidden="true">@include('partials.sport-icon', ['slug' => $sport->slug]) {{ $sport->name }}</span>
                        <div class="rp-level-control">
                            <div class="rp-level-scale">
                                @for($level = 1; $level <= 5; $level++)
                                    <input type="radio" name="levels[{{ $sport->id }}]" id="level-{{ $sport->id }}-{{ $level }}" value="{{ $level }}" @checked((string) $actual === (string) $level)>
                                    <label for="level-{{ $sport->id }}-{{ $level }}" aria-label="{{ $sport->name }}, nivel {{ $level }}">{{ $level }}</label>
                                @endfor
                            </div>
                            <label class="rp-level-unset"><input type="radio" name="levels[{{ $sport->id }}]" value="" @checked($actual === null || $actual === '')> No juego</label>
                        </div>
                    </div>
                </fieldset>
            @empty
                <p class="rp-muted">Todavía no hay deportes disponibles para configurar tus niveles.</p>
            @endforelse
            </div>
            <div class="rp-field rp-profile-name">
                <label for="public-name">Nombre público</label>
                <input id="public-name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required autocomplete="name">
            </div>
            <div class="rp-form-footer"><span class="rp-small">Tus cambios se aplican a tu perfil.</span><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
        </section>
    </div>
</form>
@endsection
