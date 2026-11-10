<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Уровень игрока по каждому виду спорта (1–5), как в Playtomic.
// Используется при записи в открытые матчи с ограничением уровня.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sport_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->timestamps();
            $table->unique(['user_id', 'sport_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sport_levels');
    }
};
