{{-- El contenedor permite actualizar únicamente los avatares del usuario guardado. --}}
<span class="rp-avatar-current" data-user-avatar="{{ $avatarUser->id }}" data-avatar-url="{{ $avatarUser->avatarUrl() ?? '' }}" data-avatar-version="{{ $avatarUser->avatar_version ?? '' }}" data-avatar-initials="{{ \App\Support\Ui::initials($avatarUser->name) }}">
    @if($avatarUser->avatarUrl())
        <img class="rp-avatar-image" src="{{ $avatarUser->avatarUrl() }}" alt="" loading="lazy" decoding="async">
    @else
        {{ \App\Support\Ui::initials($avatarUser->name) }}
    @endif
</span>
