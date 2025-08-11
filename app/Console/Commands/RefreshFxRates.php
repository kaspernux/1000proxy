<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RefreshFxRates extends Command
{
	protected $signature = 'fx:refresh {--base=USD : Base currency} {--provider=exchangerate : Provider (exchangerate|ecb|custom)}';
	protected $description = 'Refresh foreign exchange rates and cache them for currency conversions.';

	public function handle(): int
	{
		$base = strtoupper($this->option('base'));
		$provider = $this->option('provider');

		$this->info("Refreshing FX rates (base={$base}, provider={$provider})...");

		try {
			$rates = $this->fetchRates($base, $provider);
			if (empty($rates) || !is_array($rates)) {
				$this->error('No rates retrieved. Aborting.');
				return self::FAILURE;
			}

			$payload = [
				'base' => $base,
				'provider' => $provider,
				'fetched_at' => now()->toIso8601String(),
				'rates' => $rates,
			];

			Cache::put('fx:rates', $payload, now()->addHours(12));
			file_put_contents(storage_path('app/fx_rates.json'), json_encode($payload, JSON_PRETTY_PRINT));

			$this->info('Stored ' . count($rates) . ' rates.');
			return self::SUCCESS;
		} catch (\Throwable $e) {
			$this->error('FX refresh failed: ' . $e->getMessage());
			\Log::error('FX refresh failed', ['error' => $e]);
			return self::FAILURE;
		}
	}

	protected function fetchRates(string $base, string $provider): array
	{
		return match ($provider) {
			'exchangerate' => $this->fetchExchangeRateApi($base),
			'ecb' => $this->fetchEcb($base),
			default => config('currency.rates', []),
		};
	}

	protected function fetchExchangeRateApi(string $base): array
	{
		$apiKey = config('services.exchangerate.token');
		if (!$apiKey) {
			$this->warn('No exchangerate API key configured; using fallback rates.');
			return config('currency.rates', []);
		}
		$resp = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}");
		if (!$resp->ok()) {
			$this->warn('Provider responded with ' . $resp->status() . ' - using fallback.');
			return config('currency.rates', []);
		}
		return $resp->json('conversion_rates') ?? [];
	}

	protected function fetchEcb(string $base): array
	{
		$resp = Http::timeout(10)->get('https://api.exchangerate.host/latest', [
			'base' => $base,
		]);
		if (!$resp->ok()) {
			$this->warn('ECB provider failed; using fallback.');
			return config('currency.rates', []);
		}
		return $resp->json('rates') ?? [];
	}
}
