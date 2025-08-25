<?php

namespace App\Services;

class TaxService
{
    /**
     * Calculate tax amount for a given subtotal and optional region context.
     * If config('tax.mode') is 'tax_free', returns 0.
     *
     * @param float $subtotal
     * @param array $context Example: ['country' => 'US', 'state' => 'CA']
     * @return float
     */
    public function calculate(float $subtotal, array $context = []): float
    {
        $mode = config('tax.mode', 'tax_free');
        if ($mode === 'tax_free') {
            return 0.0;
        }

        $rate = $this->resolveRate($context);
        if ($rate <= 0) {
            return 0.0;
        }

        return round($subtotal * ($rate / 100), 2);
    }

    /**
     * Resolve tax rate (percent) based on context and config/tax.php
     */
    public function resolveRate(array $context = []): float
    {
        $rate = (float) config('tax.default_rate', 0.0);
        if (config('tax.mode', 'tax_free') !== 'regional') {
            return 0.0;
        }

        $map = (array) config('tax.rates', []);
        if (empty($map)) {
            return $rate;
        }

        $keys = [];
        if (!empty($context['country'])) {
            $keys[] = 'country:' . strtoupper($context['country']);
        }
        if (!empty($context['state']) && !empty($context['country'])) {
            $keys[] = 'country:' . strtoupper($context['country']) . '|state:' . strtoupper($context['state']);
        }

        // Most specific wins; check composite key first
        $keys = array_reverse($keys);
        foreach ($keys as $key) {
            if (array_key_exists($key, $map)) {
                return (float) $map[$key];
            }
        }

        return $rate;
    }
}
