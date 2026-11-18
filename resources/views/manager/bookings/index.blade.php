{{-- Supervisión de reservas: solo se muestran clubes del gestor conectado. --}}
@extends('layouts.app')
@section('title', 'Reservas del club — Reserva Pistes')
@section('banner')@include('partials.banner', ['title' => 'Reservas del club', 'subtitle' => 'Consulta la ocupación de tus pistas.'])@endsection
@section('content')
<nav class="rp-tabs" aria-label="Mi club"><a href="{{ route('manager.courts.index') }}">Pistas</a><a class="active" href="{{ route('manager.bookings.index') }}">Reservas</a></nav>
<div class="rp-list">
@forelse($bookings as $booking)
    <article class="rp-record rp-manager-booking">
        <div class="rp-calendar-tile"><strong>{{ $booking->starts_at->format('j') }}</strong><span>{{ $booking->starts_at->translatedFormat('M') }}</span></div>
        <div><h3>{{ $booking->court->name }}</h3><p class="rp-record__sub">{{ $booking->court->club->name }} · {{ $booking->court->sport->name }}</p><div class="rp-record__meta"><span>@include('partials.ui-icon', ['name' => 'clock']) {{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</span><span>@include('partials.ui-icon', ['name' => 'user']) {{ $booking->user->name }}</span><span>{{ \App\Support\Ui::euros($booking->price_total) }} · simulado</span></div></div>
        <div class="rp-record__actions"><span class="rp-badge {{ $booking->status === 'cancelled' ? 'rp-badge--muted' : '' }}">{{ $booking->status === 'cancelled' ? 'Cancelada' : ($booking->is_open ? 'Partido abierto' : 'Confirmada') }}</span></div>
    </article>
@empty
    @include('partials.empty-state', ['title' => 'Todavía no hay reservas en tus clubes', 'text' => 'Cuando alguien reserve una de tus pistas, aparecerá aquí.'])
@endforelse
</div>
{{ $bookings->links('partials.pagination') }}
@endsection
