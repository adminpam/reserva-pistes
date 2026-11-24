{{-- Una ficha de participación con aforo y jugadores obtenidos del modelo. --}}
@php($soyOrganizador = $match->user_id === auth()->id())
<article @class(['rp-record', 'rp-record--match', 'rp-record--history' => $pasado])>
    <img class="rp-record__image" src="{{ \App\Support\Ui::sportThumb($match->court->sport->slug) }}" alt="Imagen ilustrativa del deporte" loading="lazy">
    <div>
        <h3>Partido de {{ mb_strtolower($match->court->sport->name) }}</h3>
        <p class="rp-record__sub">{{ $match->court->club->name }}</p>
        <div class="rp-record__meta">
            <span>@include('partials.ui-icon', ['name' => 'calendar']) {{ $match->starts_at->translatedFormat('D, j M Y') }}</span>
            <span>@include('partials.ui-icon', ['name' => 'clock']) {{ $match->starts_at->format('H:i') }}–{{ $match->ends_at->format('H:i') }}</span>
            @if($soyOrganizador && $match->checkout)<a href="{{ route('checkout.show', $match->checkout) }}">Justificante de prueba</a>@endif
        </div>
    </div>
    <div class="rp-match-players">
        <div class="rp-players" aria-hidden="true">
            @foreach($match->players->take(4) as $player)<span class="rp-player" title="{{ $player->name }}">@include('partials.user-avatar', ['avatarUser' => $player])</span>@endforeach
            @if($match->players->count() > 4)<span class="rp-player">+{{ $match->players->count() - 4 }}</span>@endif
            @for($i = 0; $i < min(3, $match->freeSlots()); $i++)<span class="rp-player rp-player--empty">+</span>@endfor
        </div>
        <p class="rp-player-count">{{ $match->players->count() }}/{{ $match->court->sport->players_per_match }} jugadores</p>
    </div>
    <div class="rp-match-actions">
        @if($match->status === 'cancelled')<span class="rp-badge rp-badge--muted">Cancelado</span>
        @elseif($pasado)<span class="rp-badge rp-badge--muted">Jugado</span>
        @elseif($match->starts_at->isPast())<span class="rp-badge rp-badge--amber">En curso</span>
        @else
            @if($soyOrganizador)<span class="rp-badge rp-badge--amber">Organizas tú</span>@endif
            <form method="POST" action="{{ route('matches.leave', $match) }}" data-confirm="{{ $soyOrganizador ? 'Se cancelará el partido para todos los jugadores. ¿Continuar?' : '¿Quieres salir de este partido?' }}">
                @csrf @method('DELETE')<button class="btn {{ $soyOrganizador ? 'btn-danger' : 'btn-ghost' }}" type="submit">{{ $soyOrganizador ? 'Cancelar partido' : 'Salir' }}</button>
            </form>
        @endif
    </div>
</article>
