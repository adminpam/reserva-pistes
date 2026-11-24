{{-- Ficha de reserva: muestra la fecha y el importe guardados al reservar. --}}
<article @class(['rp-record', 'rp-record--history' => $pasada])>
    <img class="rp-record__image" src="{{ \App\Support\Ui::sportThumb($booking->court->sport->slug) }}" alt="Imagen ilustrativa del deporte" loading="lazy">
    <div>
        <h3>{{ $booking->court->club->name }}</h3>
        <p class="rp-record__sub">{{ $booking->court->name }} · {{ $booking->court->sport->name }}</p>
        <div class="rp-record__meta">
            <span>@include('partials.ui-icon', ['name' => 'calendar']) {{ $booking->starts_at->translatedFormat('D, j M Y') }}</span>
            <span>@include('partials.ui-icon', ['name' => 'clock']) {{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</span>
            <span>{{ \App\Support\Ui::euros($booking->price_total) }} · simulado</span>
            @if($booking->checkout)<a href="{{ route('checkout.show', $booking->checkout) }}">Justificante de prueba</a>@endif
        </div>
    </div>
    <div class="rp-record__actions">
        @if($booking->status === 'cancelled')
            <span class="rp-badge rp-badge--muted">Cancelada</span>
        @elseif($pasada)
            <span class="rp-badge rp-badge--muted">@include('partials.ui-icon', ['name' => 'check']) Jugada</span>
        @elseif($booking->starts_at->isPast())
            <span class="rp-badge rp-badge--amber">En curso</span>
        @else
            <span class="rp-badge">@include('partials.ui-icon', ['name' => 'check']) Confirmada</span>
            <form class="rp-btn-group" method="POST" action="{{ route('bookings.cancel', $booking) }}" data-confirm="¿Cancelar esta reserva? La hora volverá a estar disponible.">
                @csrf @method('PATCH')<button class="btn btn-danger" type="submit">Cancelar</button>
            </form>
        @endif
    </div>
</article>
