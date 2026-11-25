<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

// Настоящие виды спорта с реальным числом игроков матча:
// от players_per_match считаются места в открытых матчах.
class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            ['name' => 'Tenis de mesa', 'slug' => 'tenis-de-mesa', 'players_per_match' => 2, 'icon' => 'tenis-de-mesa.svg'],
            ['name' => 'Bádminton', 'slug' => 'badminton', 'players_per_match' => 4, 'icon' => 'badminton.svg'],
            ['name' => 'Baloncesto 3x3', 'slug' => 'baloncesto-3x3', 'players_per_match' => 6, 'icon' => 'baloncesto-3x3.svg'],
            ['name' => 'Voleibol', 'slug' => 'voleibol', 'players_per_match' => 12, 'icon' => 'voleibol.svg'],
            ['name' => 'Fútbol sala', 'slug' => 'futbol-sala', 'players_per_match' => 10, 'icon' => 'futbol-sala.svg'],
            ['name' => 'Tenis', 'slug' => 'tenis', 'players_per_match' => 2, 'icon' => 'tenis.svg'],
        ];

        foreach ($sports as $sport) {
            Sport::updateOrCreate(['slug' => $sport['slug']], $sport);
        }
        // Pádel se añade sin sobrescribir un aforo ya administrado.
        Sport::firstOrCreate(['slug' => 'padel'], ['name' => 'Pádel', 'players_per_match' => 4, 'icon' => 'padel.svg']);
    }
}
