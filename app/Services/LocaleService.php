<?php

namespace App\Services;

class LocaleService
{
    public static function supported(): array
    {
        return config('locales.supported', ['en']);
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }

    public static function normalize(string $locale): string
    {
        $locale = strtolower(str_replace(['_', '-'], '-', $locale));
        if (self::isSupported($locale)) {
            return $locale;
        }
        // try language only part
        if (str_contains($locale, '-')) {
            $base = explode('-', $locale)[0];
            if (self::isSupported($base)) {
                return $base;
            }
        }
        return config('locales.default', 'en');
    }

    public static function fallback(): string
    {
        return config('locales.fallback', 'en');
    }
}
