{{-- Historial real: las canceladas se separan para no llamarlas jugadas. --}}
@extends('layouts.app')
@section('title', 'Mis reservas — Reserva Pistes')
@section('banner')
    @include('partials.banner', ['title' => 'Mis reservas', 'subtitle' => 'Aquí tienes tus reservas actuales y pasadas.'])
@endsection
@section('content')
@php
    $proximas = $bookings->filter(fn ($b) => $b->status !== 'cancelled' && $b->ends_at->isFuture())->sortBy('starts_at');
    $pasadas = $bookings->filter(fn ($b) => $b->status !== 'cancelled' && $b->ends_at->isPast());
    $canceladas = $bookings->where('status', 'cancelled');
@endphp
@foreach(['Próximas' => $proximas, 'Pasadas' => $pasadas, 'Canceladas' => $canceladas] as $heading => $items)
    @if($items->isNotEmpty())
        <h2 class="rp-section-heading">{{ $heading }}</h2>
        <div class="rp-list">@foreach($items as $booking)@include('bookings._row', ['booking' => $booking, 'pasada' => $heading !== 'Próximas'])@endforeach</div>
    @endif
@endforeach
@if($bookings->isEmpty())
    @include('partials.empty-state', ['title' => 'Todavía no tienes reservas', 'text' => 'Busca un club y reserva tu pista en unos clics.', 'href' => route('home'), 'label' => 'Buscar un club'])
@endif
@endsection
