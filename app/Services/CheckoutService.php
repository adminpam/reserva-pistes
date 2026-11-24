<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\Court;
use App\Models\DemoPayment;
use App\Models\Sport;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function begin(User $user, array $input): BookingCheckout
    {
        $key = 'checkout-create:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['hour' => 'Has realizado muchas solicitudes. Espera un minuto antes de repetir.']);
        }
        RateLimiter::hit($key, 60);
        $isOpen = in_array($input['is_open'] ?? false, [true, 1, '1'], true);
        $data = Validator::make($input, [
            'checkout_key' => ['required', 'uuid'],
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'hour' => ['required', 'integer', 'min:0', 'max:23'],
            'is_open' => ['sometimes', 'boolean'],
            'min_level' => [Rule::excludeIf(! $isOpen), 'nullable', 'integer', 'min:1', 'max:5'],
            'max_level' => [Rule::excludeIf(! $isOpen), 'nullable', 'integer', 'min:1', 'max:5'],
        ], ['checkout_key.required' => 'Recarga la ficha del club antes de reservar.'])->validate();
        $minimum = isset($data['min_level']) ? (int) $data['min_level'] : null;
        $maximum = isset($data['max_level']) ? (int) $data['max_level'] : null;
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw ValidationException::withMessages(['max_level' => 'El nivel máximo debe ser igual o superior al mínimo.']);
        }
        $id = strtolower($data['checkout_key']);
        $startsAt = Carbon::parse($data['date'])->setTime((int) $data['hour'], 0);
        // Esta lectura orientativa se hace antes de la transacción. Dentro se
        // vuelve a comprobar la relación tras bloquear usuario, deporte y pista.
        $sportId = Court::whereKey($data['court_id'])->value('sport_id');
        abort_if($sportId === null, 404);

        try {
            return DB::transaction(function () use ($user, $data, $isOpen, $minimum, $maximum, $id, $startsAt, $sportId): BookingCheckout {
                $owner = User::query()->lockForUpdate()->findOrFail($user->id);
                Sport::query()->lockForUpdate()->findOrFail($sportId);
                $court = Court::query()->lockForUpdate()->findOrFail($data['court_id']);
                if ((int) $court->sport_id !== (int) $sportId) {
                    throw ValidationException::withMessages(['court_id' => 'La pista ha cambiado. Recarga la ficha antes de reservar.']);
                }
                $existing = BookingCheckout::query()->lockForUpdate()->find($id);
                if ($existing) {
                    abort_unless((int) $existing->user_id === $owner->id, 403);
                    if (! $this->sameSelection($existing, $court, $startsAt, $isOpen, $minimum, $maximum)) {
                        throw ValidationException::withMessages(['checkout_key' => 'Esta solicitud ya corresponde a otra selección. Recarga la ficha para elegir una nueva.']);
                    }

                    return $this->expire($existing);
                }
                if ($startsAt->toDateString() < now()->toDateString()) {
                    throw ValidationException::withMessages(['date' => 'No se puede reservar en el pasado.']);
                }
                if (! $startsAt->isFuture()) {
                    throw ValidationException::withMessages(['hour' => 'No se puede reservar en el pasado.']);
                }
                $hour = (int) $data['hour'];
                if ($hour < $court->open_hour || $hour >= $court->close_hour) {
                    throw ValidationException::withMessages(['hour' => 'La pista está cerrada a esa hora.']);
                }
                $limits = new Booking(['min_level' => $minimum, 'max_level' => $maximum]);
                if ($isOpen && ! $limits->levelFits($owner->levelFor($court->sport_id))) {
                    throw ValidationException::withMessages(['min_level' => 'Elige un rango que incluya tu nivel. Puedes indicar tu nivel en el perfil.']);
                }
                $endsAt = $startsAt->copy()->addHour();
                $own = BookingCheckout::active()->where('user_id', $owner->id)->where('court_id', $court->id)
                    ->where('starts_at', $startsAt)->where('ends_at', $endsAt)->first();
                if ($own && $this->sameSelection($own, $court, $startsAt, $isOpen, $minimum, $maximum)) {
                    return $own; // Reanudar no amplía la caducidad ni cambia la tarifa.
                }
                if (Booking::overlaps($court->id, $startsAt, $endsAt)) {
                    throw ValidationException::withMessages(['hour' => 'Ese horario está reservado o tiene un pago pendiente. Elige otro o consulta tus pagos de prueba.']);
                }
                if ($owner->checkouts()->active()->count() >= 3) {
                    throw ValidationException::withMessages(['hour' => 'Tienes tres reservas pendientes. Completa o cancela alguna antes de iniciar otra.']);
                }
                $expiresAt = now()->addMinutes(5);
                if ($expiresAt->gt($startsAt)) {
                    $expiresAt = $startsAt->copy();
                }

                return BookingCheckout::create([
                    'id' => $id, 'user_id' => $owner->id, 'court_id' => $court->id,
                    'starts_at' => $startsAt, 'ends_at' => $endsAt, 'expires_at' => $expiresAt,
                    'amount_cents' => $court->price_per_hour, 'is_open' => $isOpen,
                    'min_level' => $minimum, 'max_level' => $maximum, 'status' => 'pending',
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // Una colisión entre usuarios o pistas distintas también termina
            // fuera de la transacción abortada, sin devolver un error SQL.
            $existing = BookingCheckout::find($id);
            if (! $existing) {
                throw $exception;
            }
            abort_unless((int) $existing->user_id === $user->id, 403);
            $court = Court::findOrFail($data['court_id']);
            if (! $this->sameSelection($existing, $court, $startsAt, $isOpen, $minimum, $maximum)) {
                throw ValidationException::withMessages(['checkout_key' => 'Esta solicitud ya corresponde a otra selección. Recarga la ficha.']);
            }

            return $this->refresh($user, $existing);
        }
    }

    public function refresh(User $user, BookingCheckout $checkout): BookingCheckout
    {
        return DB::transaction(function () use ($user, $checkout): BookingCheckout {
            $current = BookingCheckout::query()->lockForUpdate()->findOrFail($checkout->id);
            abort_unless((int) $current->user_id === $user->id, 403);

            return $this->expire($current);
        }, 3);
    }

    public function pay(User $user, BookingCheckout $checkout, Closure $outcome): BookingCheckout
    {
        return $this->change($user, $checkout, function (BookingCheckout $current, User $owner, Court $court) use ($outcome): BookingCheckout {
            $this->expire($current);
            if ($current->status !== 'pending') {
                return $current; // Repetir el POST nunca crea otro pago o reserva.
            }
            $limits = new Booking(['min_level' => $current->min_level, 'max_level' => $current->max_level]);
            $reason = null;
            if (Booking::overlaps($court->id, $current->starts_at, $current->ends_at, $current->id)) {
                $reason = 'slot_unavailable';
            } elseif ($current->starts_at->hour < $court->open_hour || $current->ends_at->gt($current->starts_at->copy()->startOfDay()->addHours($court->close_hour))) {
                $reason = 'hours_changed';
            } elseif ($current->is_open && ! $limits->levelFits($owner->levelFor($court->sport_id))) {
                $reason = 'level_changed';
            }
            if ($reason) {
                $current->update(['status' => 'conflict', 'failure_reason' => $reason]);

                return $current;
            }
            $decision = $outcome(); // Solo se valida la tarjeta si aún se puede pagar.
            if (! in_array($decision, ['approved', 'declined'], true)) {
                throw new \LogicException('Resultado interno de simulación no válido.');
            }
            $this->expire($current); // También se comprueba la hora después de validar.
            if ($current->status !== 'pending') {
                return $current;
            }
            $booking = null;
            if ($decision === 'approved') {
                $booking = Booking::create([
                    'court_id' => $court->id, 'user_id' => $owner->id,
                    'starts_at' => $current->starts_at, 'ends_at' => $current->ends_at,
                    'status' => 'confirmed', 'price_total' => $current->amount_cents,
                    'is_open' => $current->is_open, 'min_level' => $current->min_level, 'max_level' => $current->max_level,
                ]);
                if ($current->is_open) {
                    $booking->players()->attach($owner->id);
                }
            }
            DemoPayment::create([
                'checkout_id' => $current->id, 'booking_id' => $booking?->id,
                'reference' => 'DEMO-'.strtoupper(str_replace('-', '', $current->id)),
                'status' => $decision, 'amount_cents' => $current->amount_cents, 'processed_at' => now(),
            ]);
            $current->update(['status' => $decision === 'approved' ? 'paid' : 'declined', 'booking_id' => $booking?->id]);

            return $current;
        });
    }

    public function cancel(User $user, BookingCheckout $checkout): BookingCheckout
    {
        return $this->change($user, $checkout, function (BookingCheckout $current): BookingCheckout {
            $this->expire($current);
            if ($current->status === 'pending') {
                $current->update(['status' => 'cancelled']);
            }

            return $current;
        });
    }

    private function change(User $user, BookingCheckout $checkout, Closure $action): BookingCheckout
    {
        abort_unless((int) $checkout->user_id === $user->id, 403);
        $sportId = Court::whereKey($checkout->court_id)->value('sport_id');
        abort_if($sportId === null, 404);

        return DB::transaction(function () use ($user, $checkout, $sportId, $action): BookingCheckout {
            $owner = User::query()->lockForUpdate()->findOrFail($user->id);
            Sport::query()->lockForUpdate()->findOrFail($sportId);
            $court = Court::query()->lockForUpdate()->findOrFail($checkout->court_id);
            $current = BookingCheckout::query()->lockForUpdate()->findOrFail($checkout->id);
            abort_unless((int) $current->user_id === $owner->id, 403);
            abort_unless((int) $current->court_id === $court->id && (int) $court->sport_id === (int) $sportId, 409);

            return $action($current, $owner, $court);
        }, 3);
    }

    private function expire(BookingCheckout $checkout): BookingCheckout
    {
        if ($checkout->status === 'pending' && ! $checkout->isActive()) {
            $checkout->update(['status' => 'expired']);
        }

        return $checkout;
    }

    private function sameSelection(BookingCheckout $checkout, Court $court, Carbon $start, bool $open, ?int $min, ?int $max): bool
    {
        return (int) $checkout->court_id === $court->id && $checkout->starts_at->eq($start)
            && $checkout->is_open === $open && $checkout->min_level === $min && $checkout->max_level === $max;
    }
}
