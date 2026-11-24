@extends('layouts.app')
@section('title', 'Mis pagos de prueba — Reserva Pistes')
@push('styles')<link rel="stylesheet" href="{{ \App\Support\FrontendAsset::url('ui/checkout.css') }}">@endpush
@section('banner')
@include('partials.banner', ['title' => 'Mis pagos de prueba', 'subtitle' => 'Solicitudes, resultados y justificantes de tu cuenta.'])
@endsection
@section('content')
<p class="rp-notice">Demostración educativa. Todos los importes y pagos son simulados; no se realiza ningún cobro real.</p>
<div class="rp-checkout__actions"><a href="{{ route('bookings.index') }}">Mis reservas</a><a href="{{ route('matches.mine') }}">Mis partidos</a></div>
<div class="rp-list">
@forelse($checkouts as $checkout)
    @include('checkout._row', ['checkout' => $checkout])
@empty
    @include('partials.empty-state', ['title' => 'Todavía no tienes pagos de prueba', 'text' => 'Elige una pista y una hora para comenzar.', 'href' => route('home'), 'label' => 'Buscar un club'])
@endforelse
</div>
{{ $checkouts->links('partials.pagination') }}
@endsection
