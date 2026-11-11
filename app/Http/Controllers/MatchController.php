<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Открытые матчи: поиск, запись, выход и список «мои матчи».
// Матч — это бронь с is_open = true; участники лежат в booking_players.
class MatchController extends Controller
{
    // Поиск матчей с фильтрами: вид спорта, «подходит моему уровню», текст клуба.
    public function index(Request $request): View
    {
        $matches = Booking::openUpcoming()
            ->with('court.club', 'court.sport', 'players')
            ->orderBy('starts_at');

        if ($sport = $request->query('sport')) {
            $matches->whereHas('court.sport', fn ($q) => $q->where('slug', $sport));
        }
        if ($club = $request->query('club')) {
            $matches->whereHas('court.club', fn ($q) => $q->where('name', 'like', "%{$club}%"));
        }

        $matches = $matches->get();

        // Фильтр по уровню считается в PHP: уровень зависит от вида спорта
        // конкретного матча, одним SQL-запросом это не выразить просто.
        if ($request->boolean('my_level')) {
            $user = $request->user();
            $matches = $matches->filter(
                fn (Booking $m) => $m->levelFits($user->levelFor($m->court->sport_id))
            )->values();
        }

        return view('matches.index', [
            'matches' => $matches,
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    // «Мои матчи»: и созданные мной, и те, куда я записался.
    public function mine(Request $request): View
    {
        $user = $request->user();

        $matches = Booking::where('is_open', true)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('players', fn ($p) => $p->where('users.id', $user->id));
            })
            ->with('court.club', 'court.sport', 'players')
            ->orderByDesc('starts_at')
            ->get();

        return view('matches.mine', ['matches' => $matches]);
    }

    // Запись в матч: проверяем статус, свободные места и диапазон уровня.
    public function join(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();

        if (! $booking->is_open || $booking->status !== 'confirmed' || $booking->starts_at->isPast()) {
            return back()->withErrors(['match' => 'Este partido ya no está disponible.']);
        }
        if ($booking->players->contains('id', $user->id)) {
            return back()->withErrors(['match' => 'Ya estás apuntado a este partido.']);
        }
        if ($booking->freeSlots() < 1) {
            return back()->withErrors(['match' => 'El partido está completo.']);
        }
        if (! $booking->levelFits($user->levelFor($booking->court->sport_id))) {
            return back()->withErrors(['match' => 'Tu nivel no entra en el rango de este partido. Ajusta tu nivel en el perfil.']);
        }

        $booking->players()->attach($user->id);

        return back()->with('ok', '¡Apuntado al partido!');
    }

    // Выход из матча. Если выходит создатель — матч отменяется целиком:
    // без организатора бронь площадки не имеет смысла.
    public function leave(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();

        if ($booking->user_id === $user->id) {
            $booking->update(['status' => 'cancelled']);
            return back()->with('ok', 'Partido cancelado (eras el organizador).');
        }

        $booking->players()->detach($user->id);

        return back()->with('ok', 'Has salido del partido.');
    }
}
