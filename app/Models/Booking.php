<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    // Главное бизнес-правило проекта: слот нельзя забронировать,
    // если на этой площадке уже есть подтверждённая бронь,
    // пересекающаяся по времени (start < other_end AND end > other_start).
    public static function overlaps(int $courtId, $startsAt, $endsAt): bool
    {
        return static::where('court_id', $courtId)
            ->where('status', 'confirmed')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
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
