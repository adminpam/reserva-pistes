<article class="rp-panel rp-checkout-row">
    <div><h3>{{ $checkout->court->club->name }}</h3><p>{{ $checkout->court->name }} · {{ $checkout->court->sport->name }}</p><p class="rp-small">{{ $checkout->starts_at->format('d/m/Y H:i') }}–{{ $checkout->ends_at->format('H:i') }} · {{ $checkout->is_open ? 'Partido abierto' : 'Privada' }}</p></div>
    <div><span class="rp-badge">{{ $checkout->statusLabel() }}</span><p>{{ \App\Support\Ui::euros($checkout->amount_cents) }} · simulado</p></div>
    <a class="btn btn-ghost" href="{{ route('checkout.show', $checkout) }}">{{ $checkout->isActive() ? 'Completar pago' : 'Ver detalle' }}</a>
</article>
