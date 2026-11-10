<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Брони. Одна запись = один часовой слот площадки.
// user_id — кто создал бронь. status: confirmed или cancelled.
// is_open = true превращает бронь в «открытый матч», к которому могут
// присоединяться другие игроки (через booking_players).
// min_level / max_level — необязательный диапазон уровня для открытого матча.
// price_total — имитация оплаты: цена фиксируется на момент брони.
// Индекс по (court_id, starts_at) ускоряет проверку пересечения слотов.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('confirmed');
            $table->boolean('is_open')->default(false);
            $table->unsignedTinyInteger('min_level')->nullable();
            $table->unsignedTinyInteger('max_level')->nullable();
            $table->unsignedInteger('price_total');
            $table->timestamps();
            $table->index(['court_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
