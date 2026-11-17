{{-- Reservas de los clubes del gestor. Solo lectura: supervisa la ocupación. --}}
@extends('layouts.app')
@section('title', 'Reservas del club — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Reservas del club',
        'subtitle' => 'Todas las reservas de tus pistas, de la más reciente a la más antigua.',
        'image'    => \App\Support\Ui::sportCover('baloncesto-3x3'),
    ])
@endsection

@section('content')
@forelse($bookings as $booking)
    <article @class(['card', 'row-card', 'is-past' => $booking->status === 'cancelled'])>
        <div class="date-block">
            <b>{{ $booking->starts_at->format('j') }}</b>
            <span>{{ $booking->starts_at->translatedFormat('M') }}</span>
        </div>

        <div style="min-width:112px">
            <strong>{{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</strong>
            <p class="tiny">{{ $booking->starts_at->translatedFormat('l') }}</p>
        </div>

        <div class="grow">
            <h3>{{ $booking->court->name }}</h3>
            <p class="muted">{{ $booking->court->club->name }} · {{ $booking->court->sport->name }}</p>
        </div>

        <div class="inline" style="gap:10px;min-width:180px">
            <span class="avatar">{{ \App\Support\Ui::initials($booking->user->name) }}</span>
            <span style="font-weight:600;font-size:.9375rem">{{ $booking->user->name }}</span>
        </div>

        <strong style="min-width:76px;text-align:right">{{ \App\Support\Ui::euros($booking->price_total) }}</strong>

        <div class="row-actions">
            @if($booking->status === 'cancelled')
                <span class="chip chip-muted">Cancelada</span>
            @elseif($booking->is_open)
                <span class="chip chip-amber">Partido abierto</span>
            @else
                <span class="chip">Confirmada</span>
            @endif
        </div>
    </article>
@empty
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>Todavía no hay reservas en tus clubes</h3>
        <p>Cuando alguien reserve una de tus pistas, aparecerá aquí.</p>
    </div>
@endforelse

{{ $bookings->links() }}
@endsection
