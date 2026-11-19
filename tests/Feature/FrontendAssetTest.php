<?php

namespace Tests\Feature;

use App\Support\FrontendAsset;
use Tests\TestCase;

// Проверяет обновление URL после изменения файла. Не использует базу данных.
class FrontendAssetTest extends TestCase
{
    public function test_changing_asset_bytes_changes_its_url_even_with_the_same_filename(): void
    {
        $path = 'ui/cache-test-'.bin2hex(random_bytes(8)).'.txt';
        $file = public_path($path);

        try {
            file_put_contents($file, 'primera version');
            $first = FrontendAsset::url($path);
            file_put_contents($file, 'segunda version');
            $second = FrontendAsset::url($path);

            $this->assertNotSame($first, $second);
            $this->assertSame(strtok($first, '?'), strtok($second, '?'));
            $this->assertSame($second, FrontendAsset::url($path));
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function test_asset_url_keeps_the_configured_public_origin_and_path(): void
    {
        $url = FrontendAsset::url('ui/screens.css');

        $this->assertStringStartsWith(asset('ui/screens.css').'?v=', $url);
        $this->assertMatchesRegularExpression('/[?]v=[a-f0-9]{16}$/', $url);
    }
}
