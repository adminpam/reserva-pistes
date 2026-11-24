<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Simulación interna: estos dos identificadores son los únicos admitidos.
class DemoPaymentInput
{
    public const APPROVED_CARD = '4242424242424242';
    public const DECLINED_CARD = '4000000000000002';

    public static function outcome(mixed $input): string
    {
        if (is_array($input) && is_string($input['number'] ?? null)) {
            $input['number'] = preg_replace('/[\s-]+/', '', $input['number']);
        }
        $data = Validator::make(['payment' => $input], [
            'payment' => ['required', 'array:holder,number,expiry,cvc'],
            'payment.holder' => ['required', 'string', Rule::in(['Persona Demo'])],
            'payment.number' => ['required', 'string', Rule::in([self::APPROVED_CARD, self::DECLINED_CARD])],
            'payment.expiry' => ['bail', 'required', 'string', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/',
                function ($attribute, $value, $fail): void {
                    [$month, $year] = explode('/', $value);
                    if (Carbon::create(2000 + (int) $year, (int) $month, 1)->endOfMonth()->isPast()) {
                        $fail('Usa una caducidad de prueba actual o futura.');
                    }
                }],
            'payment.cvc' => ['required', 'string', Rule::in(['123'])],
        ], [
            'payment.required' => 'Completa los datos ficticios de la simulación.',
            'payment.array' => 'Los datos de prueba no tienen un formato válido.',
            'payment.holder.in' => 'Usa únicamente el titular ficticio Persona Demo.',
            'payment.number.in' => 'Solo se admiten las dos tarjetas ficticias indicadas. No introduzcas una tarjeta real.',
            'payment.cvc.in' => 'El código de prueba es 123.',
            'payment.expiry.regex' => 'La caducidad debe tener formato MM/AA.',
        ])->validate();

        return $data['payment']['number'] === self::APPROVED_CARD ? 'approved' : 'declined';
    }
}
