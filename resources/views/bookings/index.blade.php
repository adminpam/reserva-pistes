{{-- «Мои брони»: список обычных броней с отменой будущих. --}}
@extends('layouts.app')
@section('title', 'Mis reservas — ReservaPistes')

@section('content')
<h1>Mis reservas</h1>

@forelse($bookings as $booking)
    <div class="card row-card @if($booking->status === 'cancelled') is-cancelled @endif">
        <div>
            <strong>{{ $booking->court->club->name }}</strong> — {{ $booking->court->name }}
            <p class="muted">
                {{ $booking->court->sport->name }} ·
                {{ $booking->starts_at->format('d/m/Y H:i') }}–{{ $booking->ends_at->format('H:i') }} ·
                {{ number_format($booking->price_total / 100, 2, ',', '.') }} € (simulado)
            </p>
        </div>
        <div class="row-actions">
            @if($booking->status === 'cancelled')
                <span class="badge badge-muted">Cancelada</span>
            @elseif($booking->starts_at->isFuture())
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                      onsubmit="return confirm('¿Cancelar esta reserva?')">
                    @csrf @method('PATCH')
                    <button class="btn btn-danger" type="submit">Cancelar</button>
                </form>
            @else
                <span class="badge">Jugada</span>
            @endif
        </div>
    </div>
@empty
    <div class="empty-state">
        <img src="{{ asset('img/empty.svg') }}" alt="" width="120">
        <p>Todavía no tienes reservas. <a href="{{ route('home') }}">Busca un club en el mapa</a>.</p>
    </div>
@endforelse
@endsection
