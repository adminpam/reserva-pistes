{{-- Reservas de los clubes del gestor, paginadas. --}}
@extends('layouts.app')
@section('title', 'Reservas del club — ReservaPistes')

@section('content')
<h1>Reservas de mis clubes</h1>

@forelse($bookings as $booking)
    <div class="card row-card @if($booking->status === 'cancelled') is-cancelled @endif">
        <div>
            <strong>{{ $booking->court->club->name }}</strong> — {{ $booking->court->name }}
            <p class="muted">
                {{ $booking->starts_at->format('d/m/Y H:i') }} ·
                {{ $booking->user->name }} ·
                {{ $booking->is_open ? 'Partido abierto' : 'Reserva' }} ·
                {{ number_format($booking->price_total / 100, 2, ',', '.') }} €
            </p>
        </div>
        <span class="badge {{ $booking->status === 'cancelled' ? 'badge-muted' : 'badge-ok' }}">
            {{ $booking->status === 'cancelled' ? 'Cancelada' : 'Confirmada' }}
        </span>
    </div>
@empty
    <p class="muted">Todavía no hay reservas en tus clubes.</p>
@endforelse

{{ $bookings->links() }}
@endsection
