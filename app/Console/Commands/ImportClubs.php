<?php

namespace App\Console\Commands;

use App\Models\Club;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// Importa clubes reales desde OpenStreetMap con la Overpass API (sin clave).
// Busca centros deportivos (leisure=sports_centre y leisure=sports_hall)
// en la zona Sabadell - Terrassa - Barcelona y hace upsert por osm_id,
// asi que ejecutarlo varias veces no crea duplicados.
// Uso: php artisan clubs:import
class ImportClubs extends Command
{
    protected $signature = 'clubs:import {--limit=60 : Maximo de clubes a importar}';

    protected $description = 'Importa clubes deportivos reales desde OpenStreetMap (Overpass API)';

    // Bounding box aproximado: sur/oeste = Barcelona, norte/este = Terrassa-Sabadell.
    private const BBOX = '41.33,1.95,41.62,2.30';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info('Consultando Overpass API (puede tardar unos segundos)...');

        // Overpass QL: nodos y ways con leisure=sports_centre|sports_hall y nombre.
        // "out center" añade el centro geometrico de los ways para tener lat/lng.
        $query = sprintf(
            '[out:json][timeout:60];(node["leisure"~"sports_centre|sports_hall"]["name"](%1$s);way["leisure"~"sports_centre|sports_hall"]["name"](%1$s););out center %2$d;',
            self::BBOX,
            $limit,
        );

        $response = Http::timeout(90)
            ->withHeaders(['User-Agent' => 'ReservaPistes/1.0 (proyecto educativo IES Sabadell)'])
            ->asForm()
            ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);

        if (! $response->successful()) {
            $this->error('Overpass ha respondido con error HTTP '.$response->status());
            return self::FAILURE;
        }

        $elements = $response->json('elements', []);
        $this->info('Overpass ha devuelto '.count($elements).' elementos.');

        $created = 0;
        $updated = 0;

        foreach ($elements as $el) {
            // Los nodos traen lat/lon directos; los ways, en la clave "center".
            $lat = $el['lat'] ?? $el['center']['lat'] ?? null;
            $lng = $el['lon'] ?? $el['center']['lon'] ?? null;
            $name = $el['tags']['name'] ?? null;

            if (! $lat || ! $lng || ! $name) {
                continue;
            }

            $osmId = $el['type'].'/'.$el['id'];

            // Direccion: primero las etiquetas addr:* de OSM; si no hay,
            // geocodificacion inversa con Nominatim (max. 1 peticion/segundo
            // segun su politica de uso, de ahi el sleep).
            $address = $this->addressFromTags($el['tags'] ?? []);
            $city = $el['tags']['addr:city'] ?? null;

            if (! $address) {
                [$address, $city] = $this->reverseGeocode((float) $lat, (float) $lng);
                sleep(1);
            }

            $club = Club::updateOrCreate(
                ['osm_id' => $osmId],
                [
                    'name' => mb_substr($name, 0, 150),
                    'address' => $address,
                    'city' => $city,
                    'lat' => $lat,
                    'lng' => $lng,
                ],
            );

            $club->wasRecentlyCreated ? $created++ : $updated++;
            $this->line(($club->wasRecentlyCreated ? '[+] ' : '[~] ').$name);
        }

        $this->info("Hecho: {$created} clubes nuevos, {$updated} actualizados.");
        $this->comment('Recuerda asignar gestores y crear pistas desde el panel de admin/manager.');

        return self::SUCCESS;
    }

    // Monta "calle numero" a partir de las etiquetas addr:* si existen.
    private function addressFromTags(array $tags): ?string
    {
        if (empty($tags['addr:street'])) {
            return null;
        }

        return trim($tags['addr:street'].' '.($tags['addr:housenumber'] ?? ''));
    }

    // Nominatim reverse geocoding (sin clave). Si falla, devolvemos nulls
    // y el club queda sin direccion — se puede completar a mano.
    private function reverseGeocode(float $lat, float $lng): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'ReservaPistes/1.0 (proyecto educativo IES Sabadell)'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'jsonv2',
                ]);

            if (! $response->successful()) {
                return [null, null];
            }

            $addr = $response->json('address', []);
            $street = trim(($addr['road'] ?? '').' '.($addr['house_number'] ?? ''));
            $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null;

            return [$street ?: null, $city];
        } catch (\Exception) {
            return [null, null];
        }
    }
}
