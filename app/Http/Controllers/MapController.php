<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Sport;
use App\Support\Ui;
use App\Support\SportSelection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Portada y datos del mapa. Solo usa clubes y deportes de la base de datos;
// una base vacía se representa como tal, sin inventar clubes para el diseño.
class MapController extends Controller
{
    public function index(Request $request): View
    {
        $order = ['tenis-de-mesa', 'badminton', 'baloncesto-3x3', 'voleibol', 'futbol-sala', 'tenis', 'padel'];
        $sports = Sport::orderBy('name')->get()->sortBy(function (Sport $sport) use ($order): int {
            $position = array_search($sport->slug, $order, true);

            return $position === false ? count($order) : $position;
        })->values();

        return view('map.index', ['sports' => $sports, 'selectedSportSlugs' => SportSelection::fromRequest($request)]);
    }

    public function clubs(Request $request): JsonResponse
    {
        $selectedSports = SportSelection::fromRequest($request);
        $query = Club::with(['courts' => function ($courts) use ($selectedSports): void {
            $courts->with('sport');
            if ($selectedSports !== []) {
                $courts->whereHas('sport', fn ($sport) => $sport->whereIn('slug', $selectedSports));
            }
        }]);
        if ($selectedSports !== []) {
            $query->whereHas('courts.sport', fn ($sport) => $sport->whereIn('slug', $selectedSports));
        }

        $clubs = $query->orderBy('name')->get()->map(function (Club $club) use ($selectedSports): array {
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
                'sport_slugs' => $sports->pluck('slug')->all(),
                'court_count' => $club->courts->count(),
                'url' => route('clubs.show', ['club' => $club->id] + ($selectedSports ? ['sports' => $selectedSports] : [])),
                // Las imágenes del deporte son ilustraciones, no fotos verificadas del club.
                'image_url' => $first ? Ui::sportThumb($first->slug) : asset('img/club-cover.svg'),
                'image_description' => $first ? 'Imagen ilustrativa: '.$first->name : 'Ilustración de un club deportivo',
            ];
        });

        return response()->json($clubs)->header('Cache-Control', 'no-store');
    }
}
