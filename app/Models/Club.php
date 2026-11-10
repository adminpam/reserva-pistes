<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Клуб со спортивными площадками. Координаты (lat/lng) показываются на карте,
// osm_id связывает запись с объектом OpenStreetMap при импорте.
class Club extends Model
{
    protected $fillable = [
        'name', 'address', 'city', 'lat', 'lng', 'osm_id', 'manager_id', 'description',
    ];

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Список уникальных видов спорта клуба — для фильтра на карте и карточки клуба.
    public function sportList()
    {
        return $this->courts->pluck('sport')->unique('id')->values();
    }
}
