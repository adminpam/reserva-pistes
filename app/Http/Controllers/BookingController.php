<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Reserva una hora y conserva el precio de la pista en ese momento.
// Las cancelaciones cambian el estado: nunca borran el historial.
class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->where('is_open', false)
            ->with('court.club', 'court.sport')
            ->orderByDesc('starts_at')
            ->get();

        return view('bookings.index', ['bookings' => $bookings]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isOpen = $request->boolean('is_open');
        $data = $request->validate([
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hour' => ['required', 'integer', 'min:0', 'max:23'],
            'is_open' => ['sometimes', 'boolean'],
            'min_level' => [Rule::excludeIf(! $isOpen), 'nullable', 'integer', 'min:1', 'max:5'],
            'max_level' => [Rule::excludeIf(! $isOpen), 'nullable', 'integer', 'min:1', 'max:5'],
        ]);
        $minimum = $data['min_level'] ?? null;
        $maximum = $data['max_level'] ?? null;
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw ValidationException::withMessages(['max_level' => 'El nivel máximo debe ser igual o superior al mínimo.']);
        }

        // La pista siempre existe aunque aún no haya reservas. Bloquearla hace
        // que dos solicitudes de la misma pista comprueben el solapamiento por turno.
        $booking = DB::transaction(function () use ($request, $data, $isOpen, $minimum, $maximum): Booking {
            $court = Court::query()->lockForUpdate()->findOrFail($data['court_id']);
            $hour = (int) $data['hour'];
            if ($hour < $court->open_hour || $hour >= $court->close_hour) {
                throw ValidationException::withMessages(['hour' => 'La pista está cerrada a esa hora.']);
            }

            $startsAt = Carbon::parse($data['date'])->setTime($hour, 0);
            if (! $startsAt->isFuture()) {
                throw ValidationException::withMessages(['hour' => 'No se puede reservar en el pasado.']);
            }
            $endsAt = $startsAt->copy()->addHour();
            if (Booking::overlaps($court->id, $startsAt, $endsAt)) {
                throw ValidationException::withMessages(['hour' => 'Ese horario acaba de ser reservado. Elige otro.']);
            }

            // El importe y el propietario salen del servidor, no del formulario.
            // Los límites son opcionales: admitimos mínimo, máximo o ambos.
            $booking = new Booking([
                'court_id' => $court->id,
                'user_id' => $request->user()->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
                'is_open' => $isOpen,
                'min_level' => $minimum,
                'max_level' => $maximum,
                'price_total' => $court->price_per_hour,
            ]);
            if ($isOpen && ! $booking->levelFits($request->user()->levelFor($court->sport_id))) {
                throw ValidationException::withMessages(['min_level' => 'Elige un rango que incluya tu nivel. Puedes indicar tu nivel en el perfil.']);
            }

            $booking->save();
            if ($isOpen) {
                $booking->players()->attach($request->user()->id);
            }

            return $booking;
        }, 3);

        $message = $booking->is_open
            ? 'Partido abierto creado. ¡Ya estás apuntado!'
            : 'Reserva confirmada (pago simulado).';

        return redirect()->route($booking->is_open ? 'matches.mine' : 'bookings.index')->with('ok', $message);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        // El mismo bloqueo se usa al entrar o salir de un partido. Una cancelación
        // y una incorporación no pueden comprobar estados distintos a la vez.
        DB::transaction(function () use ($request, $booking): void {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->user_id === $request->user()->id, 403);
            if ($current->status === 'cancelled') {
                return;
            }
            if (! $current->starts_at->isFuture()) {
                throw ValidationException::withMessages(['booking' => 'No se puede cancelar una reserva pasada o en curso.']);
            }
            $current->update(['status' => 'cancelled']);
        }, 3);

        return back()->with('ok', 'Reserva cancelada.');
    }
}
