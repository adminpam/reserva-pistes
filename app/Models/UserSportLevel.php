<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Самооценка уровня игрока (1–5) по одному виду спорта.
class UserSportLevel extends Model
{
    protected $fillable = ['user_id', 'sport_id', 'level'];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }
}
