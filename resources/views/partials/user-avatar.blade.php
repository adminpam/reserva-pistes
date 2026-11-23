{{-- La foto es visible para usuarios autenticados; sin foto se usan las iniciales. --}}
@if($avatarUser->avatarUrl())
    <img class="rp-avatar-image" src="{{ $avatarUser->avatarUrl() }}" alt="" loading="lazy" decoding="async">
@else
    {{ \App\Support\Ui::initials($avatarUser->name) }}
@endif
