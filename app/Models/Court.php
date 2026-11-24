<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Площадка клуба: вид спорта, цена за час (в центах) и часы работы.
class Court extends Model
{
    protected $fillable = [
        'club_id', 'sport_id', 'name', 'price_per_hour',
        'open_hour', 'close_hour', 'is_outdoor',
    ];

    protected function casts(): array
    {
        return ['is_outdoor' => 'boolean'];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(BookingCheckout::class);
    }

    // Цена в евро для показа в интерфейсе (в БД — центы).
    public function priceEuros(): string
    {
        return number_format($this->price_per_hour / 100, 2, ',', '.');
    }

    // Часы, занятые подтверждёнными бронями в указанную дату.
    // Используется на странице клуба, чтобы пометить слоты как занятые.
    public function busyHoursOn(string $date): array
    {
        return $this->bookings()
            ->where('status', 'confirmed')
            ->whereDate('starts_at', $date)
            ->get()
            ->map(fn ($b) => (int) $b->starts_at->format('G'))
            ->all();
    }
}
