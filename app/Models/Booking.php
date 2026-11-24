<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// Бронь часового слота. При is_open = true это «открытый матч»:
// другие игроки занимают места через booking_players.
class Booking extends Model
{
    protected $fillable = [
        'court_id', 'user_id', 'starts_at', 'ends_at', 'status',
        'is_open', 'min_level', 'max_level', 'price_total',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_open' => 'boolean',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'booking_players')->withTimestamps();
    }

    public function checkout(): HasOne
    {
        return $this->hasOne(BookingCheckout::class, 'booking_id');
    }

    // Una sola consulta UNION ve reservas y retenciones en la misma instantánea.
    // Los dos modelos tienen court_id/starts_at/ends_at y comparten esos casts.
    public static function occupiedIntervals(array $courtIds, $start, $end = null, ?string $ignoreCheckout = null, $at = null): Builder
    {
        $bookings = static::query()->select('court_id', 'starts_at', 'ends_at')->where('status', 'confirmed');
        $holds = BookingCheckout::active($at)->select('court_id', 'starts_at', 'ends_at');
        if ($ignoreCheckout !== null) {
            $holds->where('id', '!=', $ignoreCheckout);
        }
        foreach ([$bookings, $holds] as $query) {
            $query->whereIn('court_id', $courtIds)->where('ends_at', '>', $start);
            if ($end !== null) {
                $query->where('starts_at', '<', $end);
            }
        }

        return $bookings->unionAll($holds);
    }

    public static function overlaps(int $courtId, $startsAt, $endsAt, ?string $ignoreCheckout = null): bool
    {
        return static::occupiedIntervals([$courtId], $startsAt, $endsAt, $ignoreCheckout)->exists();
    }

    // Se llama dentro de la transacción que ya bloqueó esta reserva.
    // Conserva el justificante y su referencia; repetir no duplica la reversión.
    public function cancelWithDemoRefund(): void
    {
        if ($this->status !== 'cancelled') {
            $this->update(['status' => 'cancelled']);
        }
        DemoPayment::where('booking_id', $this->id)->where('status', 'approved')
            ->update(['status' => 'refunded', 'refunded_at' => now()]);
    }

    // Scope: только будущие подтверждённые открытые матчи — для страницы поиска.
    public function scopeOpenUpcoming(Builder $query): Builder
    {
        return $query->where('is_open', true)
            ->where('status', 'confirmed')
            ->where('starts_at', '>', now());
    }

    // Свободные места = вместимость вида спорта минус записавшиеся игроки.
    public function freeSlots(): int
    {
        return max(0, $this->court->sport->players_per_match - $this->players->count());
    }

    // Подходит ли уровень игрока под диапазон матча (если диапазон задан).
    public function levelFits(?int $level): bool
    {
        if ($this->min_level === null && $this->max_level === null) {
            return true;
        }
        if ($level === null) {
            return false;
        }
        return $level >= ($this->min_level ?? 1) && $level <= ($this->max_level ?? 5);
    }
}
