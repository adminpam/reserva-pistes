{{-- Perfil: nombre público y nivel 1-5 por deporte. Nivel vacío = no juega. --}}
@extends('layouts.app')
@section('title', 'Mi perfil — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Mi perfil',
        'subtitle' => 'Gestiona tus datos y tus niveles de juego.',
        'image'    => \App\Support\Ui::sportCover('tenis'),
    ])
@endsection

@section('content')
<form method="POST" action="{{ route('profile.update') }}">
    @csrf @method('PATCH')

    <div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start" class="profile-grid">

        <aside class="card" style="text-align:center">
            <span class="avatar avatar-lg" style="margin:8px auto 18px">{{ \App\Support\Ui::initials($user->name) }}</span>
            <h3>{{ $user->name }}</h3>
            <p class="muted">{{ $user->email }}</p>
            <p class="tiny" style="margin-top:18px;padding-top:16px;border-top:1px solid var(--line)">
                Miembro desde {{ $user->created_at->translatedFormat('F \d\e Y') }}
            </p>
            @if($user->isManager() || $user->isAdmin())
                <span class="chip" style="margin-top:14px">{{ $user->isAdmin() ? 'Administrador' : 'Gestor de club' }}</span>
            @endif
        </aside>

        <div class="card">
            <h2 class="section-title">Mis niveles</h2>
            <p class="muted" style="margin-bottom:22px">
                1 es principiante y 5 avanzado. Tu nivel se usa en los partidos que piden un rango.
            </p>

            <div class="stack">
                @foreach($sports as $sport)
                    @php($actual = $user->levelFor($sport->id))
                    <div class="inline" style="justify-content:space-between;gap:20px">
                        <span class="inline" style="gap:12px;min-width:190px">
                            <img src="{{ asset('img/sports/'.$sport->icon) }}" alt="" width="26" height="26">
                            <strong>{{ $sport->name }}</strong>
                        </span>

                        <div class="level">
                            <input type="radio" name="levels[{{ $sport->id }}]" id="lv{{ $sport->id }}-0"
                                   value="" @checked($actual === null)>
                            <label for="lv{{ $sport->id }}-0" title="No juego">—</label>
                            @for($l = 1; $l <= 5; $l++)
                                <input type="radio" name="levels[{{ $sport->id }}]" id="lv{{ $sport->id }}-{{ $l }}"
                                       value="{{ $l }}" @checked($actual === $l)>
                                <label for="lv{{ $sport->id }}-{{ $l }}">{{ $l }}</label>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="field" style="margin-top:30px;padding-top:24px;border-top:1px solid var(--line)">
                <label for="name">Nombre público</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                       required maxlength="100">
            </div>

            <div class="inline" style="justify-content:flex-end;margin-top:8px">
                <button class="btn btn-primary" type="submit">Guardar cambios</button>
            </div>
        </div>
    </div>
</form>

<style>
/* El selector de nivel tiene seis opciones aquí (incluye "no juego") */
.profile-grid .level { grid-template-columns: repeat(6, minmax(46px, 1fr)); }
@media (max-width: 1024px) {
    .profile-grid { grid-template-columns: 1fr !important; }
    .profile-grid .inline { flex-direction: column; align-items: stretch !important; gap: 10px !important; }
    .profile-grid .level { width: 100%; }
}
</style>
@endsection
