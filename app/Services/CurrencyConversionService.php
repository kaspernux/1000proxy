<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CurrencyConversionService
{
    protected array $rates;

    public function __construct()
    {
        $this->rates = config('currency.rates', ['usd' => 1.0]);
    }

    public function toUSD(float $amount, string $currency): float
    {
        $code = strtolower($currency);
        $rate = $this->rates[$code] ?? null;
        if ($rate === null || $rate <= 0) {
            Log::warning('Missing FX rate; passthrough used', ['currency' => $currency]);
            return round($amount, 2);
        }
        return round($amount / $rate, 2);
    }
}
