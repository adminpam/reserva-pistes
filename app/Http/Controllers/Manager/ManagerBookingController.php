<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Reservas de los clubes del gestor: proximas primero, con jugador,
// pista y estado. Solo lectura — el gestor supervisa la ocupacion.
class ManagerBookingController extends Controller
{
    public function index(Request $request): View
    {
        $clubIds = $request->user()->managedClubs()->pluck('id');

        $bookings = Booking::whereHas('court', fn ($q) => $q->whereIn('club_id', $clubIds))
            ->with('court.club', 'court.sport', 'user')
            ->orderByDesc('starts_at')
            ->paginate(25);

        return view('manager.bookings.index', ['bookings' => $bookings]);
    }
}
