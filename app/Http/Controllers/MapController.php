<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Pagina principal: mapa Leaflet con los clubes y filtro por deporte.
// La vista carga los marcadores llamando al endpoint JSON /api/clubs.
class MapController extends Controller
{
    public function index(): View
    {
        return view('map.index', [
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    // Devuelve los clubes en JSON para pintar los marcadores del mapa.
    // Con ?sport=slug solo salen los clubes que tienen pistas de ese deporte.
    public function clubs(Request $request): JsonResponse
    {
        $query = Club::with('courts.sport');

        if ($slug = $request->query('sport')) {
            $query->whereHas('courts.sport', fn ($q) => $q->where('slug', $slug));
        }

        $clubs = $query->get()->map(fn (Club $club) => [
            'id' => $club->id,
            'name' => $club->name,
            'address' => $club->address,
            'lat' => (float) $club->lat,
            'lng' => (float) $club->lng,
            'sports' => $club->sportList()->pluck('name')->all(),
            'url' => route('clubs.show', $club),
        ]);

        return response()->json($clubs);
    }
}
