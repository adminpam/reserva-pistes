<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sports')->insertOrIgnore([
            'slug' => 'padel', 'name' => 'Pádel', 'players_per_match' => 4, 'icon' => 'padel.svg',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $sportId = DB::table('sports')->where('slug', 'padel')->value('id');
        foreach (DB::table('clubs')->orderBy('id')->limit(3)->get(['id']) as $index => $club) {
            if (DB::table('courts')->where('club_id', $club->id)->where('sport_id', $sportId)->exists()) {
                continue;
            }
            DB::table('courts')->insert([
                'club_id' => $club->id, 'sport_id' => $sportId, 'name' => '[Demo] Pista pádel',
                'price_per_hour' => 1400 + $index * 200, 'open_hour' => 8, 'close_hour' => 22,
                'is_outdoor' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Se conserva el deporte y las pistas: pueden tener reservas/niveles/pagos.
        // Esta migración de catálogo no elimina historial al revertir su marca.
    }
};
