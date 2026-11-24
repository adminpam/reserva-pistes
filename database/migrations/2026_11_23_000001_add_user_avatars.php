<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_version', 64)->nullable();
        });
        // Foto pequeña separada: las listas de usuarios no cargan los bytes.
        // longText sirve también con MySQL; PostgreSQL/SQLite lo guardan como texto.
        Schema::create('user_avatars', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('mime_type', 30);
            $table->longText('image_base64');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_avatars');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_version');
        });
    }
};
