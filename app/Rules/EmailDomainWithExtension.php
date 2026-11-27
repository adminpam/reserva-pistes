<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Política del registro: dominio con extensión. Complementa la regla email de
// Laravel sin consultar DNS, para admitir también direcciones ficticias .test.
class EmailDomainWithExtension implements ValidationRule
{
    public const MESSAGE = 'Introduce un correo con dominio y extensión, por ejemplo nombre@dominio.com.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $position = is_string($value) ? strrpos($value, '@') : false;
        $domain = $position === false ? '' : substr($value, $position + 1);
        // Se admiten subdominios, etiquetas internacionales y su formato punycode.
        $pattern = '/\A(?:[\p{L}\p{N}](?:[\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?\.)+(?:\p{L}{2,63}|xn--[a-z0-9-]{2,59})\z/iu';
        if (preg_match($pattern, $domain) !== 1) {
            $fail(self::MESSAGE);
        }
    }
}
