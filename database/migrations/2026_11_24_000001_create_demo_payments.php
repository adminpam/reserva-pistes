<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_checkouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('expires_at');
            $table->unsignedInteger('amount_cents');
            $table->boolean('is_open')->default(false);
            $table->unsignedTinyInteger('min_level')->nullable();
            $table->unsignedTinyInteger('max_level')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('failure_reason', 40)->nullable();
            $table->timestamps();
            $table->index(['court_id', 'status', 'expires_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('demo_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('checkout_id')->unique()->constrained('booking_checkouts')->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('status', 20);
            $table->unsignedInteger('amount_cents');
            $table->dateTime('processed_at');
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_payments');
        Schema::dropIfExists('booking_checkouts');
    }
};
