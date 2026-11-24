<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Club;
use Illuminate\Support\Carbon;

// Calcula la misma disponibilidad para la ficha inicial y su actualización JSON.
// Solo devuelve horas: nunca nombres, usuarios ni información privada de reservas.
class ClubAvailability
{
    public function forDate(Club $club, string $date): array
    {
        $club->loadMissing('courts');
        $day = Carbon::parse($date)->startOfDay();
        $nextDay = $day->copy()->addDay();
        $checkedAt = now();

        // UNION evita una falsa hora libre si el pago transforma una retención
        // en reserva entre dos lecturas separadas. No se devuelven IDs de pago.
        $bookings = Booking::occupiedIntervals($club->courts->pluck('id')->all(), $day, $nextDay, null, $checkedAt)
            ->get()->groupBy('court_id');

        $courts = [];
        foreach ($club->courts->sortBy('id') as $court) {
            $hours = [];
            $busy = $bookings->get($court->id, collect());
            for ($hour = (int) $court->open_hour; $hour < (int) $court->close_hour; $hour++) {
                $start = $day->copy()->setTime($hour, 0);
                $end = $start->copy()->addHour();
                $overlaps = $busy->contains(fn ($booking) => $booking->starts_at->lt($end)
                    && $booking->ends_at->gt($start));
                $hours[] = ['hour' => $hour, 'available' => $start->gt($checkedAt) && !$overlaps];
            }
            $courts[] = ['id' => $court->id, 'hours' => $hours];
        }

        return ['date' => $date, 'checked_at' => $checkedAt->toIso8601String(), 'courts' => $courts];
    }
}
