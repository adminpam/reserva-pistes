@extends('layouts.app')
@section('title', 'Pago de prueba — Reserva Pistes')
@push('styles')
<link rel="stylesheet" href="{{ \App\Support\FrontendAsset::url('ui/checkout.css') }}">
<script type="module" src="{{ \App\Support\FrontendAsset::url('ui/checkout.js') }}"></script>
@endpush
@section('banner')
@include('partials.banner', ['title' => 'Pago de prueba', 'subtitle' => 'Completa tu reserva con una simulación sin cobros reales.'])
@endsection
@section('content')
@php($state = $checkout->displayStatus())
<p class="rp-notice">Modo de prueba. No uses datos personales ni tarjetas reales. Esta aplicación no realiza ningún cobro.</p>
<div class="rp-checkout">
    <section class="rp-panel rp-checkout__summary" aria-labelledby="summary-title">
        <h2 id="summary-title">Tu selección</h2>
        <h3>{{ $checkout->court->club->name }}</h3>
        <p>{{ $checkout->court->name }} · {{ $checkout->court->sport->name }}</p>
        <dl class="rp-checkout__details">
            <div><dt>Día</dt><dd>{{ $checkout->starts_at->translatedFormat('d M Y') }}</dd></div>
            <div><dt>Hora</dt><dd>{{ $checkout->starts_at->format('H:i') }}–{{ $checkout->ends_at->format('H:i') }}</dd></div>
            <div><dt>Modalidad</dt><dd>{{ $checkout->is_open ? 'Partido abierto' : 'Reserva privada' }}</dd></div>
            @if($checkout->is_open)<div><dt>Niveles</dt><dd>{{ $checkout->min_level ?? 'Sin mínimo' }} – {{ $checkout->max_level ?? 'Sin máximo' }}</dd></div>@endif
            <div><dt>Total simulado · 1 hora</dt><dd class="rp-checkout__amount">{{ \App\Support\Ui::euros($checkout->amount_cents) }}</dd></div>
        </dl>
        <p class="rp-small">El importe lo calcula el servidor y se mantiene durante esta solicitud. {{ $checkout->is_open ? 'El organizador simula el pago completo; unirse al partido no genera otro pago.' : '' }}</p>
        <a href="{{ route('checkout.index') }}">Mis pagos de prueba</a>
    </section>
    <section class="rp-panel rp-checkout__body" aria-labelledby="payment-title">
        <h2 id="payment-title">{{ $checkout->statusLabel() }}</h2>
        @if($state === 'pending')
            <p data-checkout-clock data-expires="{{ $checkout->expires_at->toIso8601String() }}" data-server-now="{{ $serverNow->toIso8601String() }}">Hora retenida hasta las {{ $checkout->expires_at->format('H:i:s') }}. <span data-countdown role="timer" aria-label="Tiempo restante"></span></p>
            <p data-expiry-message role="status" hidden>El tiempo ha terminado. Actualiza el estado antes de continuar.</p>
            <p class="rp-small">Rellena automáticamente una de las dos tarjetas ficticias o escribe esos mismos datos. Caducidad MM/AA actual o futura; titular Persona Demo; CVC 123.</p>
            <div class="rp-checkout__actions" data-demo-tools hidden>
                <button class="btn btn-ghost" type="button" data-demo-card="4242424242424242">Prueba aprobada · 4242</button>
                <button class="btn btn-ghost" type="button" data-demo-card="4000000000000002">Prueba rechazada · 0002</button>
            </div>
            <p class="rp-small">Aprobar: 4242 4242 4242 4242. Rechazar: 4000 0000 0000 0002.</p>
            <form method="POST" action="{{ route('checkout.pay', $checkout) }}" class="rp-checkout__form" data-payment-form autocomplete="off">
                @csrf
                <div class="rp-field"><label for="demo-holder">Titular ficticio</label><input id="demo-holder" name="payment[holder]" type="text" maxlength="30" placeholder="Persona Demo" required></div>
                <div class="rp-field"><label for="demo-number">Número de prueba</label><input id="demo-number" name="payment[number]" type="text" inputmode="numeric" maxlength="23" placeholder="4242 4242 4242 4242" required></div>
                <div class="rp-checkout__short-fields">
                    <div class="rp-field"><label for="demo-expiry">Caducidad de prueba</label><input id="demo-expiry" name="payment[expiry]" type="text" inputmode="numeric" maxlength="5" placeholder="MM/AA" pattern="(0[1-9]|1[0-2])/[0-9]{2}" required></div>
                    <div class="rp-field"><label for="demo-cvc">CVC ficticio</label><input id="demo-cvc" name="payment[cvc]" type="text" inputmode="numeric" maxlength="3" placeholder="123" pattern="123" required></div>
                </div>
                <p class="rp-small">Estos campos se validan sin guardar los datos de la tarjeta. Solo se registra el resultado y el importe de la simulación.</p>
                <button type="submit" class="btn btn-primary" data-pay-button>Simular pago de {{ \App\Support\Ui::euros($checkout->amount_cents) }}</button>
            </form>
            <div class="rp-checkout__actions">
                <form method="POST" action="{{ route('checkout.cancel', $checkout) }}" data-confirm="¿Cancelar esta solicitud y liberar la hora?">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Cancelar solicitud</button></form>
                <a class="btn btn-ghost" href="{{ route('checkout.show', $checkout) }}">Actualizar estado</a>
            </div>
        @elseif(in_array($state, ['paid', 'refunded'], true))
            <p role="status">{{ $state === 'refunded' ? 'La reserva está cancelada. Su pago de prueba se ha revertido; no hubo un cobro real.' : 'Tu reserva está confirmada. No se ha realizado ningún cobro real.' }}</p>
            <dl class="rp-checkout__details">
                <div><dt>Referencia de prueba</dt><dd class="rp-checkout__reference">{{ $checkout->payment->reference }}</dd></div>
                <div><dt>Fecha del resultado</dt><dd>{{ $checkout->payment->processed_at->format('d/m/Y H:i:s') }}</dd></div>
                @if($checkout->payment->refunded_at)<div><dt>Reversión simulada</dt><dd>{{ $checkout->payment->refunded_at->format('d/m/Y H:i:s') }}</dd></div>@endif
            </dl>
            <a class="btn btn-primary" href="{{ $checkout->is_open ? route('matches.mine') : route('bookings.index') }}">{{ $checkout->is_open ? 'Ver mis partidos' : 'Ver mis reservas' }}</a>
        @else
            <p role="status">{{ match ($state) {
                'declined' => 'La tarjeta de prueba ha simulado un rechazo. No se ha creado ninguna reserva y la hora se ha liberado.',
                'cancelled' => 'Has cancelado la solicitud. No se ha creado una reserva y la hora se ha liberado.',
                'expired' => 'Han terminado los cinco minutos o ha comenzado la hora elegida. La retención ya no bloquea la pista.',
                default => 'La disponibilidad, el horario o tu nivel han cambiado. No se ha confirmado ni pagado la reserva.',
            } }}</p>
            @if($checkout->payment)<p class="rp-small rp-checkout__reference">Referencia de prueba: {{ $checkout->payment->reference }}</p>@endif
            <p>Si vuelves a intentarlo, comprobaremos la disponibilidad y te mostraremos el importe vigente antes de pagar.</p>
            <div class="rp-checkout__actions">
                @if($checkout->starts_at->isFuture())
                <form method="POST" action="{{ route('checkout.retry', $checkout) }}">@csrf<input type="hidden" name="checkout_key" value="{{ \Illuminate\Support\Str::uuid() }}"><button class="btn btn-primary" type="submit">Consultar de nuevo</button></form>
                @endif
                <a class="btn btn-ghost" href="{{ route('clubs.show', ['club' => $checkout->court->club_id, 'date' => max(now()->toDateString(), $checkout->starts_at->toDateString())]) }}">Elegir otra hora</a>
            </div>
        @endif
    </section>
</div>
@endsection
