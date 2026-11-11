<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Создание и отмена броней. Оплата имитируется: price_total просто
// фиксирует цену площадки на момент брони, об этом есть пометка в интерфейсе.
class BookingController extends Controller
{
    // «Мои брони»: обычные брони пользователя, без открытых матчей
    // (матчи показываются отдельно в «Мои партидos»).
    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->where('is_open', false)
            ->with('court.club', 'court.sport')
            ->orderByDesc('starts_at')
            ->get();

        return view('bookings.index', ['bookings' => $bookings]);
    }

    // Создаёт бронь часового слота. Транзакция + lockForUpdate защищают
    // от гонки: два игрока не смогут занять один слот одновременно.
    // Если отмечен чекбокс is_open — создаётся открытый матч, и создатель
    // сразу записывается в booking_players.
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'hour' => ['required', 'integer', 'min:0', 'max:23'],
            'is_open' => ['sometimes', 'boolean'],
            'min_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'max_level' => ['nullable', 'integer', 'min:1', 'max:5', 'gte:min_level'],
        ]);

        $court = Court::with('club', 'sport')->findOrFail($data['court_id']);

        // Слот должен попадать в часы работы площадки.
        if ($data['hour'] < $court->open_hour || $data['hour'] >= $court->close_hour) {
            return back()->withErrors(['hour' => 'La pista está cerrada a esa hora.']);
        }

        $startsAt = \Carbon\Carbon::parse($data['date'])->setTime((int) $data['hour'], 0);
        $endsAt = $startsAt->copy()->addHour();

        if ($startsAt->isPast()) {
            return back()->withErrors(['hour' => 'No se puede reservar en el pasado.']);
        }

        $isOpen = $request->boolean('is_open');

        $booking = null;
        try {
            \DB::transaction(function () use (&$booking, $court, $startsAt, $endsAt, $isOpen, $data, $request) {
                // Блокируем пересекающиеся брони этой площадки до конца транзакции.
                $conflict = Booking::where('court_id', $court->id)
                    ->where('status', 'confirmed')
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt)
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    throw new \RuntimeException('slot-taken');
                }

                $booking = Booking::create([
                    'court_id' => $court->id,
                    'user_id' => $request->user()->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'confirmed',
                    'is_open' => $isOpen,
                    'min_level' => $isOpen ? $data['min_level'] : null,
                    'max_level' => $isOpen ? $data['max_level'] : null,
                    'price_total' => $court->price_per_hour,
                ]);

                if ($isOpen) {
                    $booking->players()->attach($request->user()->id);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['hour' => 'Ese horario acaba de ser reservado. Elige otro.']);
        }

        $msg = $isOpen
            ? 'Partido abierto creado. ¡Ya estás apuntado!'
            : 'Reserva confirmada (pago simulado).';

        return redirect()->route($isOpen ? 'matches.mine' : 'bookings.index')->with('ok', $msg);
    }

    // Отмена своей брони. Статус меняется на cancelled (не удаляем запись —
    // история остаётся, а слот снова становится свободным).
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403);
        }
        if ($booking->starts_at->isPast()) {
            return back()->withErrors(['booking' => 'No se puede cancelar una reserva pasada.']);
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('ok', 'Reserva cancelada.');
    }
}
