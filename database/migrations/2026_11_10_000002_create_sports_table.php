<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Справочник видов спорта. players_per_match — полное число игроков матча
// (например, мини-футбол = 10): от него считаются свободные места в открытых матчах.
// icon — имя SVG-файла в public/img/sports/.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->unsignedTinyInteger('players_per_match');
            $table->string('icon', 60)->default('generic.svg');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
