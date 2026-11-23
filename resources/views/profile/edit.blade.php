@extends('layouts.app')
@section('title', 'Mi perfil — Reserva Pistes')
@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\FrontendAsset::url('ui/profile.css') }}">
    <script type="module" src="{{ \App\Support\FrontendAsset::url('ui/profile.js') }}"></script>
@endpush
@section('banner')
    @include('partials.banner', ['title' => 'Mi perfil', 'subtitle' => 'Gestiona tus datos y tus niveles de juego.'])
@endsection
@section('content')
<div class="rp-profile">
    <aside class="rp-panel rp-profile-summary">
        <div class="rp-profile-avatar" aria-hidden="true">
            <span class="rp-avatar-current" data-avatar-current>@include('partials.user-avatar', ['avatarUser' => $user])</span>
            <img class="rp-avatar-image" data-avatar-preview alt="" hidden>
        </div>
        <h2>{{ $user->name }}</h2>
        <p class="rp-profile-email">{{ $user->email }}</p>
        <p class="rp-profile-since">@include('partials.ui-icon', ['name' => 'calendar']) <span>Miembro desde {{ $user->created_at->translatedFormat('F \d\e Y') }}</span></p>
        @if($user->isManager() || $user->isAdmin())<span class="rp-badge">{{ $user->isAdmin() ? 'Administrador' : 'Gestor de club' }}</span>@endif
        <div class="rp-profile-photo-actions">
            <form method="POST" action="{{ route('profile.avatar.store') }}" enctype="multipart/form-data" data-avatar-form>
                @csrf
                <div class="rp-field"><label for="avatar">Foto de perfil</label><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required aria-describedby="avatar-help avatar-status" data-avatar-input></div>
                <p class="rp-small" id="avatar-help">JPG, PNG o WebP. Comprueba la vista previa antes de guardar.</p>
                <p class="rp-small" id="avatar-status" role="status" aria-live="polite" data-avatar-status></p>
                <button class="btn btn-primary btn-sm" type="submit" data-avatar-submit>Guardar foto</button>
            </form>
            @if($user->avatar_version)
                <form method="POST" action="{{ route('profile.avatar.destroy') }}" data-confirm="¿Eliminar tu foto de perfil y volver a mostrar tus iniciales?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" type="submit">Quitar foto</button></form>
            @endif
        </div>
    </aside>
    <div class="rp-profile-main">
        <section class="rp-panel rp-profile-editor" aria-labelledby="levels-heading">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')
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
                <div class="rp-field rp-profile-name"><label for="public-name">Nombre público</label><input id="public-name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required autocomplete="name"></div>
                <div class="rp-form-footer"><span class="rp-small">Tus cambios se aplican a tu perfil.</span><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
            </form>
        </section>
        <section class="rp-panel rp-profile-password" aria-labelledby="password-heading">
            <h2 id="password-heading">Cambiar contraseña</h2>
            <p class="rp-small">Introduce tu contraseña actual para guardar una nueva. Las otras sesiones deberán volver a entrar.</p>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PATCH')
                <div class="rp-field"><label for="current-password">Contraseña actual</label><input id="current-password" name="current_password" type="password" required autocomplete="current-password"></div>
                <div class="rp-form-grid">
                    <div class="rp-field"><label for="new-password">Nueva contraseña</label><input id="new-password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
                    <div class="rp-field"><label for="confirm-password">Repite la nueva contraseña</label><input id="confirm-password" name="password_confirmation" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
                </div>
                <div class="rp-form-footer"><span class="rp-small">Mínimo 8 caracteres.</span><button class="btn btn-primary" type="submit">Actualizar contraseña</button></div>
            </form>
        </section>
    </div>
</div>
@endsection
