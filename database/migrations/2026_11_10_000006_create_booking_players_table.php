<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Участники открытых матчей (многие-ко-многим bookings <-> users).
// Создатель матча тоже записывается сюда — так число занятых мест
// считается одним count() без особых случаев.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['booking_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_players');
    }
};
