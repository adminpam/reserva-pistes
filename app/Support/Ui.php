<?php

namespace App\Support;

// Pequeñas ayudas de presentación que usan varias vistas.
// Están aquí y no en los modelos porque son asunto de la interfaz, no del dominio.
class Ui
{
    // "Marta Jugadora" -> "MJ". Para los avatares circulares.
    public static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_filter($parts);

        if (! $parts) {
            return '??';
        }

        $first = mb_strtoupper(mb_substr(reset($parts), 0, 1));
        $last  = count($parts) > 1 ? mb_strtoupper(mb_substr(end($parts), 0, 1)) : '';

        return $first.$last;
    }

    // Foto de cabecera y miniatura según el deporte. Si el slug no tiene foto
    // propia, cae en la de tenis para no dejar la tarjeta vacía.
    private const CON_FOTO = [
        'tenis-de-mesa', 'badminton', 'baloncesto-3x3', 'voleibol', 'futbol-sala', 'tenis',
    ];

    public static function sportCover(?string $slug): string
    {
        $slug = in_array($slug, self::CON_FOTO, true) ? $slug : 'tenis';

        return asset("img/sports/{$slug}-cover.jpg");
    }

    public static function sportThumb(?string $slug): string
    {
        $slug = in_array($slug, self::CON_FOTO, true) ? $slug : 'tenis';

        return asset("img/sports/{$slug}-thumb.jpg");
    }

    // Precio en céntimos -> "12,50 €"
    public static function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }
}
