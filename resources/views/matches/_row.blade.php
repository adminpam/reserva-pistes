{{-- Una fila de partido. Se usa en "Mis partidos" para activos y pasados. --}}
@php($soyOrganizador = $match->user_id === auth()->id())
<article @class(['card', 'row-card', 'is-past' => $pasado])>
    <img class="thumb" src="{{ \App\Support\Ui::sportThumb($match->court->sport->slug) }}" alt="" loading="lazy">

    <div class="grow">
        <h3>Partido de {{ mb_strtolower($match->court->sport->name) }}</h3>
        <p class="muted">{{ $match->court->club->name }} · {{ $match->court->name }}</p>
        <p class="tiny" style="margin-top:4px">
            {{ $match->starts_at->translatedFormat('D, j M Y') }} ·
            {{ $match->starts_at->format('H:i') }}–{{ $match->ends_at->format('H:i') }}
        </p>
    </div>

    <div>
        <div class="players">
            @foreach($match->players->take(4) as $jugador)
                <span class="avatar" title="{{ $jugador->name }}">{{ \App\Support\Ui::initials($jugador->name) }}</span>
            @endforeach
            @for($i = 0; $i < min(3, $match->freeSlots()); $i++)
                <span class="slot">+</span>
            @endfor
        </div>
        <p class="tiny" style="margin-top:8px">
            {{ $match->players->count() }}/{{ $match->court->sport->players_per_match }} jugadores
        </p>
    </div>

    <div class="row-actions">
        @if($match->status === 'cancelled')
            <span class="chip chip-muted">Cancelado</span>
        @elseif($pasado)
            <span class="chip chip-muted">Jugado</span>
        @else
            @if($soyOrganizador)
                <span class="chip chip-amber">Organizas tú</span>
            @endif
            <form method="POST" action="{{ route('matches.leave', $match) }}"
                  onsubmit="return confirm('{{ $soyOrganizador ? 'Se cancelará el partido para todos. ¿Seguro?' : '¿Salir de este partido?' }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger" type="submit">
                    {{ $soyOrganizador ? 'Cancelar partido' : 'Salir' }}
                </button>
            </form>
        @endif
    </div>
</article>
