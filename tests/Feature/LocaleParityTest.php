<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

class LocaleParityTest extends TestCase
{
    /** @test */
    public function all_supported_locales_have_same_telegram_keys_as_english()
    {
        $supported = config('locales.supported');
        $baseLocale = 'en';
        $this->assertContains($baseLocale, $supported, 'English (en) must be in supported locales list');

        $base = require resource_path("lang/{$baseLocale}/telegram.php");
        $baseKeys = $this->flattenKeys($base);

        $failures = [];

        foreach ($supported as $locale) {
            $path = resource_path("lang/{$locale}/telegram.php");
            $this->assertFileExists($path, "Missing telegram.php for locale {$locale}");
            $arr = require $path;
            $keys = $this->flattenKeys($arr);

            $missing = array_values(array_diff($baseKeys, $keys));
            $extra   = array_values(array_diff($keys, $baseKeys));
            if ($missing || $extra) {
                $failures[$locale] = compact('missing', 'extra');
            }
        }

        $this->assertEmpty($failures, "Locale key parity failures: " . json_encode($failures, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $k => $v) {
            $full = $prefix === '' ? $k : $prefix.'.'.$k;
            if (is_array($v)) {
                $keys = array_merge($keys, $this->flattenKeys($v, $full));
            } else {
                $keys[] = $full;
            }
        }
        return $keys;
    }
}
