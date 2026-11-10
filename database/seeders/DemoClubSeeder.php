<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;

// Un club FICTICIO de ejemplo con pistas, para que la app no arranque vacia
// antes de ejecutar clubs:import. Queda marcado como ficticio en la descripcion.
// Se asigna al gestor demo para poder probar el panel de manager.
class DemoClubSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('email', 'gestor@reservapistes.test')->first();

        $club = Club::updateOrCreate(
            ['name' => 'Club Demo Sabadell'],
            [
                'address' => 'Carrer de Ejemplo 1',
                'city' => 'Sabadell',
                'lat' => 41.5463,
                'lng' => 2.1086,
                'manager_id' => $manager?->id,
                'description' => 'Club ficticio de demostración (datos de ejemplo, no es un club real).',
            ],
        );

        $courts = [
            ['sport' => 'tenis-de-mesa', 'name' => 'Mesa 1', 'price' => 400, 'outdoor' => false],
            ['sport' => 'badminton', 'name' => 'Pista bádminton A', 'price' => 800, 'outdoor' => false],
            ['sport' => 'futbol-sala', 'name' => 'Pista fútbol sala', 'price' => 2500, 'outdoor' => true],
            ['sport' => 'tenis', 'name' => 'Pista tenis exterior', 'price' => 1200, 'outdoor' => true],
        ];

        foreach ($courts as $c) {
            $sport = Sport::where('slug', $c['sport'])->first();
            if (! $sport) {
                continue;
            }
            Court::updateOrCreate(
                ['club_id' => $club->id, 'name' => $c['name']],
                [
                    'sport_id' => $sport->id,
                    'price_per_hour' => $c['price'],
                    'open_hour' => 8,
                    'close_hour' => 22,
                    'is_outdoor' => $c['outdoor'],
                ],
            );
        }
    }
}
