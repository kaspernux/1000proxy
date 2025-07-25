<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PriceService
{
    protected array $coingeckoIds = [
        'btc' => 'bitcoin',
        'xmr' => 'monero',
        'sol' => 'solana',
    ];

    public function getUsdPrice(string $currency): float
    {
        $id = $this->coingeckoIds[strtolower($currency)] ?? null;

        if (!$id) {
            throw new \Exception("Unsupported currency: $currency");
        }

        $response = Http::get("https://api.coingecko.com/api/v3/simple/price", [
            'ids' => $id,
            'vs_currencies' => 'usd',
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch price for $currency");
        }

        return $response->json()[$id]['usd'] ?? 0.0;
    }
}
