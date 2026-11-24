<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Un partido abierto es una reserva con participantes. Todas las altas,
// bajas y cancelaciones vuelven a consultar su estado dentro de una transacción.
class MatchController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'sport' => ['nullable', 'string', 'max:100'],
            'club' => ['nullable', 'string', 'max:150'],
            'my_level' => ['sometimes', 'boolean'],
        ]);
        $matches = Booking::openUpcoming()
            ->with('court.club', 'court.sport', 'user:id,name', 'players:id,name,avatar_version')
            ->when($filters['sport'] ?? null, fn ($query, $sport) =>
                $query->whereHas('court.sport', fn ($query) => $query->where('slug', $sport)))
            ->when($filters['club'] ?? null, fn ($query, $club) =>
                $query->whereHas('court.club', fn ($query) => $query->where('name', 'like', '%'.$club.'%')))
            ->orderBy('starts_at')
            ->get();

        if ($request->boolean('my_level')) {
            $user = $request->user();
            $user->loadMissing('sportLevels');
            $matches = $matches->filter(fn (Booking $match) =>
                $match->levelFits($user->levelFor($match->court->sport_id)))->values();
        }

        return view('matches.index', ['matches' => $matches, 'sports' => Sport::orderBy('name')->get()]);
    }

    public function mine(Request $request): View
    {
        $user = $request->user();
        $matches = Booking::where('is_open', true)
            ->where(fn ($query) => $query->where('user_id', $user->id)
                ->orWhereHas('players', fn ($players) => $players->where('users.id', $user->id)))
            ->with('court.club', 'court.sport', 'players:id,name,avatar_version')
            ->orderByDesc('starts_at')
            ->get();

        return view('matches.mine', ['matches' => $matches]);
    }

    public function join(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        DB::transaction(function () use ($user, $booking): void {
            // La baja administrativa y el alta al partido comparten este usuario.
            $player = User::query()->lockForUpdate()->findOrFail($user->id);
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $this->requireUpcomingOpenMatch($current);
            $current->load('court.sport');

            // Contamos en la base después del bloqueo, no sobre una colección
            // que pudo cargarse antes de que otra persona ocupara la última plaza.
            if ($current->players()->where('users.id', $player->id)->exists()) {
                throw ValidationException::withMessages(['match' => 'Ya estás apuntado a este partido.']);
            }
            if ($current->players()->count() >= $current->court->sport->players_per_match) {
                throw ValidationException::withMessages(['match' => 'El partido está completo.']);
            }
            if (! $current->levelFits($player->levelFor($current->court->sport_id))) {
                throw ValidationException::withMessages(['match' => 'Tu nivel no entra en el rango de este partido. Ajusta tu nivel en el perfil.']);
            }
            $current->players()->attach($player->id);
        }, 3);

        return back()->with('ok', '¡Apuntado al partido!');
    }

    public function leave(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        DB::transaction(function () use ($user, $booking): void {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $this->requireUpcomingOpenMatch($current);
            if ($current->user_id === $user->id) {
                // Se conserva la lista para mostrar quién participaba en el
                // partido cancelado. La reserva deja de bloquear la pista.
                $current->update(['status' => 'cancelled']);

                return;
            }
            abort_unless($current->players()->where('users.id', $user->id)->exists(), 403);
            $current->players()->detach($user->id);
        }, 3);

        return back()->with('ok', $booking->user_id === $user->id
            ? 'Partido cancelado (eras el organizador).'
            : 'Has salido del partido.');
    }

    // El servidor aplica esta regla aunque se envíe la URL manualmente.
    private function requireUpcomingOpenMatch(Booking $booking): void
    {
        if (! $booking->is_open || $booking->status !== 'confirmed' || ! $booking->starts_at->isFuture()) {
            throw ValidationException::withMessages(['match' => 'Este partido ya no está disponible.']);
        }
    }
}
