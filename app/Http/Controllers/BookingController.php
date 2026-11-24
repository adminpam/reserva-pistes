<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()->where('is_open', false)
            ->with('court.club', 'court.sport', 'checkout.payment')->orderByDesc('starts_at')->get();
        $pendingCheckouts = $request->user()->checkouts()->active()->where('is_open', false)
            ->with('court.club', 'court.sport', 'payment')->orderBy('starts_at')->get();

        return view('bookings.index', ['bookings' => $bookings, 'pendingCheckouts' => $pendingCheckouts]);
    }

    public function store(Request $request, CheckoutService $service): RedirectResponse
    {
        $checkout = $service->begin($request->user(), $request->only([
            'checkout_key', 'court_id', 'date', 'hour', 'is_open', 'min_level', 'max_level',
        ]));

        return redirect()->route('checkout.show', $checkout);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        DB::transaction(function () use ($request, $booking): void {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->user_id === $request->user()->id, 403);
            if ($current->status !== 'cancelled' && ! $current->starts_at->isFuture()) {
                throw ValidationException::withMessages(['booking' => 'No se puede cancelar una reserva pasada o en curso.']);
            }
            $current->cancelWithDemoRefund();
        }, 3);

        return back()->with('ok', 'Reserva cancelada. Si tenía pago de prueba, su simulación queda revertida.');
    }
}
