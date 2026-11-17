{{-- Reservas del jugador, separadas en próximas y pasadas. --}}
@extends('layouts.app')
@section('title', 'Mis reservas — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Mis reservas',
        'subtitle' => 'Aquí tienes tus reservas actuales y pasadas.',
        'image'    => \App\Support\Ui::sportCover('badminton'),
    ])
@endsection

@section('content')
@php
    $proximas = $bookings->filter(fn ($b) => $b->status !== 'cancelled' && $b->starts_at->isFuture());
    $pasadas  = $bookings->reject(fn ($b) => $b->status !== 'cancelled' && $b->starts_at->isFuture());
@endphp

@if($proximas->isNotEmpty())
    <h2 class="section-title">Próximas</h2>
    <div class="stagger">
        @foreach($proximas as $booking)
            @include('bookings._row', ['booking' => $booking, 'pasada' => false])
        @endforeach
    </div>
@endif

@if($pasadas->isNotEmpty())
    <h2 class="section-title mt-xl">Pasadas</h2>
    <div>
        @foreach($pasadas as $booking)
            @include('bookings._row', ['booking' => $booking, 'pasada' => true])
        @endforeach
    </div>
@endif

@if($bookings->isEmpty())
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>Todavía no tienes reservas</h3>
        <p>Busca un club en el mapa y reserva tu pista en unos clics.</p>
        <a class="btn btn-primary" href="{{ route('home') }}">Buscar un club</a>
    </div>
@endif
@endsection
