<?php

namespace App\Http\Controllers;

use App\Models\BookingCheckout;
use App\Services\CheckoutService;
use App\Services\DemoPaymentInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $checkouts = $request->user()->checkouts()->with('court.club', 'court.sport', 'booking', 'payment')
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(15);

        return view('checkout.index', ['checkouts' => $checkouts]);
    }

    public function show(Request $request, BookingCheckout $checkout, CheckoutService $service): View
    {
        $current = $service->refresh($request->user(), $checkout)->load('court.club', 'court.sport', 'booking', 'payment');

        return view('checkout.show', ['checkout' => $current, 'serverNow' => now()]);
    }

    public function pay(Request $request, BookingCheckout $checkout, CheckoutService $service): RedirectResponse
    {
        // Se retiran los datos de prueba de la petición antes de tocar la DB.
        // Tampoco se recuperan con old input si hay un error de validación.
        $input = $request->input('payment');
        $request->replace($request->except('payment'));
        $current = $service->pay($request->user(), $checkout, fn () => DemoPaymentInput::outcome($input));

        return redirect()->route('checkout.show', $current);
    }

    public function cancel(Request $request, BookingCheckout $checkout, CheckoutService $service): RedirectResponse
    {
        $current = $service->cancel($request->user(), $checkout);

        return redirect()->route('checkout.show', $current);
    }

    public function retry(Request $request, BookingCheckout $checkout, CheckoutService $service): RedirectResponse
    {
        $current = $service->refresh($request->user(), $checkout);
        if ($current->status === 'paid' || $current->isActive()) {
            return redirect()->route('checkout.show', $current);
        }
        $data = $request->validate(['checkout_key' => ['required', 'uuid']]);
        $next = $service->begin($request->user(), [
            'checkout_key' => $data['checkout_key'], 'court_id' => $current->court_id,
            'date' => $current->starts_at->toDateString(), 'hour' => $current->starts_at->hour,
            'is_open' => $current->is_open, 'min_level' => $current->min_level, 'max_level' => $current->max_level,
        ]);

        return redirect()->route('checkout.show', $next);
    }
}
