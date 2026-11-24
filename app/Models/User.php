<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Пользователь системы. Одна таблица на три роли (player / manager / admin):
// роль хранится в колонке role, доступ разруливает middleware EnsureRole.
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Брони, созданные пользователем (и обычные, и открытые матчи).
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Открытые матчи, в которых пользователь участвует (включая свои).
    public function joinedMatches(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_players')->withTimestamps();
    }

    // Клубы, которыми управляет менеджер.
    public function managedClubs(): HasMany
    {
        return $this->hasMany(Club::class, 'manager_id');
    }

    // Уровни игрока по видам спорта.
    public function sportLevels(): HasMany
    {
        return $this->hasMany(UserSportLevel::class);
    }

    // Уровень по конкретному виду спорта или null, если игрок его не задал.
    public function levelFor(int $sportId): ?int
    {
        return $this->sportLevels->firstWhere('sport_id', $sportId)?->level;
    }

    // La URL lleva la versión; los bytes se consultan aparte, nunca en las listas.
    public function avatarUrl(): ?string
    {
        return $this->avatar_version
            ? route('users.avatar', ['user' => $this->id, 'v' => $this->avatar_version])
            : null;
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(BookingCheckout::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
