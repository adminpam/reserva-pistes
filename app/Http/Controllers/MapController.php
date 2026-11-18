<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Sport;
use App\Support\Ui;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Portada y datos del mapa. Solo usa clubes y deportes de la base de datos;
// una base vacía se representa como tal, sin inventar clubes para el diseño.
class MapController extends Controller
{
    public function index(): View
    {
        $order = ['tenis-de-mesa', 'badminton', 'baloncesto-3x3', 'voleibol', 'futbol-sala', 'tenis'];
        $sports = Sport::orderBy('name')->get()->sortBy(function (Sport $sport) use ($order): int {
            $position = array_search($sport->slug, $order, true);

            return $position === false ? count($order) : $position;
        })->values();

        return view('map.index', ['sports' => $sports]);
    }

    public function clubs(Request $request): JsonResponse
    {
        $data = $request->validate(['sport' => ['nullable', 'string', 'max:100']]);
        $query = Club::with('courts.sport');

        if ($slug = ($data['sport'] ?? null)) {
            $query->whereHas('courts.sport', fn ($query) => $query->where('slug', $slug));
        }

        $clubs = $query->orderBy('name')->get()->map(function (Club $club): array {
            $sports = $club->courts->pluck('sport')->filter()->unique('id')->values();
            $first = $sports->first();

            return [
                'id' => $club->id,
                'name' => $club->name,
                'address' => $club->address,
                'city' => $club->city,
                'min_price_per_hour' => $club->courts->min('price_per_hour'),
                'lat' => (float) $club->lat,
                'lng' => (float) $club->lng,
                'sports' => $sports->pluck('name')->all(),
                'url' => route('clubs.show', $club),
                // Las imágenes del deporte son ilustraciones, no fotos verificadas del club.
                'image_url' => $first ? Ui::sportThumb($first->slug) : asset('img/club-cover.svg'),
                'image_description' => $first ? 'Imagen ilustrativa: '.$first->name : 'Ilustración de un club deportivo',
            ];
        });

        return response()->json($clubs);
    }
}
