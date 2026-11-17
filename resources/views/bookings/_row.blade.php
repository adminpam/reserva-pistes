{{-- Una fila de reserva, compartida por próximas y pasadas. --}}
<article @class(['card', 'row-card', 'is-past' => $pasada])>
    <img class="thumb" src="{{ \App\Support\Ui::sportThumb($booking->court->sport->slug) }}" alt="" loading="lazy">

    <div class="grow">
        <h3>{{ $booking->court->club->name }}</h3>
        <p class="muted">{{ $booking->court->name }} · {{ $booking->court->sport->name }}</p>
        <p class="tiny" style="margin-top:4px">
            {{ $booking->starts_at->translatedFormat('D, j M Y') }} ·
            {{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }} ·
            {{ \App\Support\Ui::euros($booking->price_total) }} (simulado)
        </p>
    </div>

    <div class="row-actions">
        @if($booking->status === 'cancelled')
            <span class="chip chip-muted">Cancelada</span>
        @elseif($pasada)
            <span class="chip chip-muted">Jugada</span>
        @else
            <span class="chip">✓ Confirmada</span>
            <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                  onsubmit="return confirm('¿Cancelar esta reserva?')">
                @csrf @method('PATCH')
                <button class="btn btn-danger" type="submit">Cancelar</button>
            </form>
        @endif
    </div>
</article>
