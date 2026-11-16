<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Court;
use App\Models\Sport;
use Illuminate\Database\Seeder;

// Crea pistas de ejemplo para los clubes importados de OpenStreetMap.
// OSM nos da el club real (nombre y coordenadas) pero no sus pistas ni sus
// precios, asi que aqui generamos una oferta plausible: 2 o 3 pistas por club,
// con precios dentro del rango habitual de cada deporte.
// Los precios son simulados y la interfaz lo indica en la ficha del club.
// Es idempotente: solo toca los clubes que todavia no tienen ninguna pista.
class CourtSeeder extends Seeder
{
    // Rango de precio por hora (en centimos) y si suele ser pista exterior.
    private const PERFILES = [
        'tenis-de-mesa'   => ['min' => 300,  'max' => 600,  'exterior' => false, 'nombre' => 'Mesa'],
        'badminton'       => ['min' => 600,  'max' => 1000, 'exterior' => false, 'nombre' => 'Pista bádminton'],
        'baloncesto-3x3'  => ['min' => 1000, 'max' => 1800, 'exterior' => true,  'nombre' => 'Cancha 3x3'],
        'voleibol'        => ['min' => 1200, 'max' => 2000, 'exterior' => true,  'nombre' => 'Pista voleibol'],
        'futbol-sala'     => ['min' => 2000, 'max' => 3500, 'exterior' => true,  'nombre' => 'Pista fútbol sala'],
        'tenis'           => ['min' => 1000, 'max' => 1800, 'exterior' => true,  'nombre' => 'Pista tenis'],
    ];

    public function run(): void
    {
        $sports = Sport::all()->keyBy('slug');
        $clubs = Club::doesntHave('courts')->get();

        if ($clubs->isEmpty()) {
            $this->command?->info('Todos los clubes ya tienen pistas.');
            return;
        }

        $creadas = 0;

        foreach ($clubs as $club) {
            // El id del club decide los deportes: asi el resultado es estable
            // entre ejecuciones y no cambia cada vez que se siembra.
            $slugs = array_keys(self::PERFILES);
            $cuantas = 2 + ($club->id % 2);

            for ($i = 0; $i < $cuantas; $i++) {
                $slug = $slugs[($club->id + $i) % count($slugs)];
                $sport = $sports->get($slug);

                if (! $sport) {
                    continue;
                }

                $perfil = self::PERFILES[$slug];
                // Precio estable dentro del rango, derivado del id del club.
                $precio = $perfil['min'] + (($club->id * 37) % ($perfil['max'] - $perfil['min'] + 1));
                $precio = (int) (round($precio / 50) * 50);

                Court::updateOrCreate(
                    ['club_id' => $club->id, 'name' => $perfil['nombre'].' '.($i + 1)],
                    [
                        'sport_id' => $sport->id,
                        'price_per_hour' => $precio,
                        'open_hour' => 8,
                        'close_hour' => 22,
                        'is_outdoor' => $perfil['exterior'],
                    ],
                );

                $creadas++;
            }
        }

        $this->command?->info("Pistas creadas: {$creadas} en {$clubs->count()} clubes.");
    }
}
