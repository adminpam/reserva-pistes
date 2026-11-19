<?php

namespace App\Support;

// Версия в URL меняется вместе с содержимым: после деплоя браузер не смешивает
// новую разметку со старым CSS или JavaScript из своего кеша.
final class FrontendAsset
{
    public static function url(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? hash_file('sha256', $file) : false;

        return asset($path).($version ? '?v='.substr($version, 0, 16) : '');
    }
}
