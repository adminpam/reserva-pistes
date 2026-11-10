<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Добавляет колонку role к стандартной таблице users.
// Роли: player (обычный игрок), manager (менеджер клуба), admin (администратор).
// Хранится строкой, а не enum, чтобы миграция одинаково работала в SQLite и PostgreSQL.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('player')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
