<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Вид спорта. players_per_match задаёт вместимость открытого матча.
class Sport extends Model
{
    protected $fillable = ['name', 'slug', 'players_per_match', 'icon'];

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }
}
