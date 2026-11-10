<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Клубы. lat/lng — координаты для карты Leaflet.
// osm_id — идентификатор объекта в OpenStreetMap: по нему команда clubs:import
// понимает, что клуб уже импортирован, и обновляет его вместо дубликата.
// manager_id — пользователь с ролью manager, управляющий этим клубом (может быть пустым).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('osm_id', 40)->nullable()->unique();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
