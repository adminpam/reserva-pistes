<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// El gestor administra únicamente pistas de sus clubes. El historial no se borra.
class ManagerCourtController extends Controller
{
    public function index(Request $request): View
    {
        $clubs = $request->user()->managedClubs()->with('courts.sport', 'courts.bookings')->get();

        return view('manager.courts.index', ['clubs' => $clubs]);
    }

    public function create(Request $request): View
    {
        return view('manager.courts.form', [
            'court' => null,
            'clubs' => $request->user()->managedClubs,
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data): void {
            $manager = $this->lockManager($request);
            $club = Club::query()->lockForUpdate()->findOrFail($data['club_id']);
            abort_unless((int) $club->manager_id === $manager->id, 403);
            Sport::query()->lockForUpdate()->findOrFail($data['sport_id']);
            Court::create($data);
        }, 3);

        return redirect()->route('manager.courts.index')->with('ok', 'Pista creada.');
    }

    public function edit(Request $request, Court $court): View
    {
        $this->authorizeCourt($request, $court);

        return view('manager.courts.form', [
            'court' => $court,
            'clubs' => $request->user()->managedClubs,
            'sports' => Sport::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeCourt($request, $court);
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $court, $data): void {
            $manager = $this->lockManager($request);
            // Se bloquean ambos clubes en un orden estable si la pista se traslada.
            $clubs = Club::whereIn('id', [$court->club_id, $data['club_id']])->orderBy('id')->lockForUpdate()->get();
            foreach ($clubs as $club) {
                abort_unless((int) $club->manager_id === $manager->id, 403);
            }
            abort_unless($clubs->contains('id', (int) $data['club_id']), 404);
            Sport::query()->lockForUpdate()->findOrFail($data['sport_id']);
            $current = Court::query()->lockForUpdate()->findOrFail($court->id);
            $this->authorizeCourt($request, $current);
            if (! $clubs->contains('id', (int) $current->club_id)) {
                throw ValidationException::withMessages(['club_id' => 'La pista ha cambiado de club. Recarga el formulario antes de editarla.']);
            }

            if ($current->bookings()->exists() || $current->checkouts()->exists()) {
                foreach (['club_id', 'sport_id'] as $field) {
                    if ((int) $data[$field] !== (int) $current->$field) {
                        throw ValidationException::withMessages([$field => 'Una pista con reservas o solicitudes de pago conserva su club y deporte para mantener el historial.']);
                    }
                }
            }
            if ((int) $data['open_hour'] !== (int) $current->open_hour || (int) $data['close_hour'] !== (int) $current->close_hour) {
                $this->checkBookedHours($current, (int) $data['open_hour'], (int) $data['close_hour']);
            }
            $current->update($data);
        }, 3);

        return redirect()->route('manager.courts.index')->with('ok', 'Pista actualizada.');
    }

    public function destroy(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeCourt($request, $court);
        DB::transaction(function () use ($request, $court): void {
            $manager = $this->lockManager($request);
            $club = Club::query()->lockForUpdate()->findOrFail($court->club_id);
            abort_unless((int) $club->manager_id === $manager->id, 403);
            $current = Court::query()->lockForUpdate()->findOrFail($court->id);
            $this->authorizeCourt($request, $current);
            if ((int) $current->club_id !== (int) $club->id) {
                throw ValidationException::withMessages(['court' => 'La pista ha cambiado de club. Recarga el listado antes de eliminarla.']);
            }
            if ($current->bookings()->exists() || $current->checkouts()->exists()) {
                throw ValidationException::withMessages(['court' => 'Esta pista tiene reservas o solicitudes de pago. Debe conservarse para mantener el historial.']);
            }
            $current->delete();
        }, 3);

        return back()->with('ok', 'Pista eliminada.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'name' => ['required', 'string', 'max:100'],
            'price_euros' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:500'],
            'open_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'close_hour' => ['required', 'integer', 'min:1', 'max:24', 'gt:open_hour'],
            'is_outdoor' => ['sometimes', 'boolean'],
        ], ['price_euros.decimal' => 'El precio admite como máximo dos decimales.']);
        $data['price_per_hour'] = (int) round($data['price_euros'] * 100);
        $data['is_outdoor'] = $request->boolean('is_outdoor');
        unset($data['price_euros']);

        return $data;
    }

    private function authorizeCourt(Request $request, Court $court): void
    {
        abort_unless((int) $court->club->manager_id === $request->user()->id, 403);
    }

    private function lockManager(Request $request): User
    {
        $manager = User::query()->lockForUpdate()->findOrFail($request->user()->id);
        abort_unless($manager->isManager(), 403);

        return $manager;
    }

    private function checkBookedHours(Court $court, int $open, int $close): void
    {
        $bookings = Booking::occupiedIntervals([$court->id], now())->get();
        foreach ($bookings as $booking) {
            $midnight = $booking->starts_at->copy()->startOfDay();
            if ($booking->starts_at->lt($midnight->copy()->addHours($open))
                || $booking->ends_at->gt($midnight->copy()->addHours($close))) {
                throw ValidationException::withMessages(['open_hour' => 'El nuevo horario deja fuera una reserva confirmada o una retención de pago activa. Amplía el horario o conserva el actual.']);
            }
        }
    }
}
