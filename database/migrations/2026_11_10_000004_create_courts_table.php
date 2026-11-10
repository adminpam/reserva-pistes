<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Площадки клуба. Каждая привязана к одному виду спорта.
// price_per_hour хранится в центах (integer), чтобы не терять точность на float.
// open_hour / close_hour — часы работы (например 8 и 22): из них строится сетка слотов.
// is_outdoor — уличная площадка: для неё на странице клуба показывается погода (Open-Meteo).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedInteger('price_per_hour');
            $table->unsignedTinyInteger('open_hour')->default(8);
            $table->unsignedTinyInteger('close_hour')->default(22);
            $table->boolean('is_outdoor')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
