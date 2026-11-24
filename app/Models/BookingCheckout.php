<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

// Una retención temporal no es una reserva confirmada ni un partido publicado.
class BookingCheckout extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'court_id', 'booking_id', 'starts_at', 'ends_at',
        'expires_at', 'amount_cents', 'is_open', 'min_level', 'max_level', 'status', 'failure_reason'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'expires_at' => 'datetime',
            'is_open' => 'boolean', 'amount_cents' => 'integer', 'min_level' => 'integer', 'max_level' => 'integer'];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(DemoPayment::class, 'checkout_id');
    }

    public function scopeActive(Builder $query, $at = null): Builder
    {
        $at ??= now();

        return $query->where('status', 'pending')->where('expires_at', '>', $at)->where('starts_at', '>', $at);
    }

    public function isActive(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture() && $this->starts_at->isFuture();
    }

    public function effectiveStatus(): string
    {
        return $this->status === 'pending' && ! $this->isActive() ? 'expired' : $this->status;
    }

    public function displayStatus(): string
    {
        return $this->status === 'paid' && $this->payment?->status === 'refunded' ? 'refunded' : $this->effectiveStatus();
    }

    public function statusLabel(): string
    {
        return match ($this->displayStatus()) {
            'pending' => 'Pendiente de pago', 'paid' => 'Pago de prueba aprobado',
            'declined' => 'Pago de prueba rechazado', 'cancelled' => 'Solicitud cancelada',
            'expired' => 'Tiempo agotado', 'refunded' => 'Simulación revertida',
            default => 'Reserva no disponible',
        };
    }
}
