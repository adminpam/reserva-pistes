<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// Contrato común: sin filtro = todos; sports[] = cualquiera de los elegidos.
// sport=slug conserva los enlaces y el selector anteriores.
class SportSelection
{
    public static function fromRequest(Request $request): array
    {
        $data = $request->validate([
            'sport' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'exists:sports,slug'],
            'sports' => ['sometimes', 'array', 'list', 'max:32'],
            'sports.*' => ['bail', 'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'exists:sports,slug'],
        ], [
            'sports.array' => 'Selecciona los deportes con los filtros de la página.',
            'sports.list' => 'La selección de deportes debe ser una lista.',
            'sports.max' => 'Selecciona como máximo 32 deportes.',
            'sports.*.exists' => 'Algún deporte ya no está disponible. Actualiza los filtros.',
            'sport.exists' => 'Este deporte ya no está disponible. Actualiza los filtros.',
        ]);
        $slugs = $data['sports'] ?? [];
        if (($data['sport'] ?? '') !== '') {
            if ($slugs !== []) {
                throw ValidationException::withMessages(['sports' => 'Usa sports[] o sport, sin mezclar los dos filtros.']);
            }
            $slugs = [$data['sport']];
        }
        $slugs = array_values(array_unique($slugs));
        sort($slugs, SORT_STRING);

        return $slugs;
    }
}
