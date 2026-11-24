<?php

namespace App\Support;

use Closure;

// Se comparte entre registro y cambio de contraseña. Bcrypt admite 72 bytes.
class PasswordRules
{
    public static function creation(): array
    {
        return ['bail', 'required', 'string', 'min:8', 'max:72', 'confirmed',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (strlen($value) > 72) {
                    $fail('La contraseña es demasiado larga. Usa una más corta.');
                }
                if (str_contains($value, "\0")) {
                    $fail('La contraseña contiene caracteres no admitidos.');
                }
            },
        ];
    }
}
