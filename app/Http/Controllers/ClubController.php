<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ficha del club: pistas agrupadas, precios y formulario de reserva.
// Con ?date=YYYY-MM-DD se recalculan las horas ocupadas de cada pista
// para marcar los slots no disponibles en el selector.
class ClubController extends Controller
{
    public function show(Request $request, Club $club): View
    {
        $date = $request->query('date', now()->toDateString());

        // Si la fecha que llega no es valida, volvemos a hoy sin romper la pagina.
        try {
            $date = \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Exception) {
            $date = now()->toDateString();
        }

        $club->load('courts.sport');

        $busy = [];
        foreach ($club->courts as $court) {
            $busy[$court->id] = $court->busyHoursOn($date);
        }

        return view('clubs.show', [
            'club' => $club,
            'date' => $date,
            'busy' => $busy,
            'hasOutdoor' => $club->courts->contains('is_outdoor', true),
        ]);
    }
}
