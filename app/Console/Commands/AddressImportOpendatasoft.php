<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\City;
use App\Models\PostalCode;

class AddressImportOpendatasoft extends Command
{
    protected $signature = 'address:import-opendatasoft {domain} {dataset_id} {--iso2=country_code|ISO2 field name} {--country=country|Country name field} {--city=city|City field} {--postal=postal_code|Postal code field} {--limit=100}';
    protected $description = 'Import address data from an Opendatasoft Explore API dataset into countries/cities/postal_codes';

    public function handle()
    {
        $domain = $this->argument('domain');
        $dataset = $this->argument('dataset_id');
        $iso2Field = $this->option('iso2') ?: 'country_code';
        $countryField = $this->option('country') ?: 'country';
        $cityField = $this->option('city') ?: 'city';
        $postalField = $this->option('postal') ?: 'postal_code';
        $limit = min(1000, max(10, (int)$this->option('limit')));

        $offset = 0;
        $totalImported = 0;

        $this->info("Importing from {$domain} dataset {$dataset} (limit={$limit})");

        while (true) {
            $url = "https://{$domain}/api/explore/v2.1/catalog/datasets/{$dataset}/records?limit={$limit}&offset={$offset}";
            try {
                $resp = Http::acceptJson()->get($url);
            } catch (\Throwable $e) {
                $this->error('HTTP request failed: ' . $e->getMessage());
                return 1;
            }

            if (!$resp->ok()) {
                $this->error('Request failed: HTTP ' . $resp->status());
                return 1;
            }

            $payload = $resp->json();
            $items = $payload['results'] ?? $payload['records'] ?? $payload['data'] ?? [];
            if (empty($items)) break;

            foreach ($items as $item) {
                // records may wrap fields under 'fields' key
                $fields = $item['fields'] ?? $item;
                $iso2 = strtoupper(trim($fields[$iso2Field] ?? ($fields['country_code'] ?? '')));
                $countryName = trim($fields[$countryField] ?? ($fields['country'] ?? ''));
                $cityName = trim($fields[$cityField] ?? ($fields['city'] ?? ''));
                $postal = trim($fields[$postalField] ?? ($fields['postal_code'] ?? ''));

                if (empty($iso2) && empty($countryName)) continue;

                $country = null;
                if (!empty($iso2)) {
                    $country = Country::firstOrCreate(['iso2' => $iso2], ['name' => $countryName ?: $iso2]);
                } else {
                    // try by name
                    $country = Country::firstOrCreate(['name' => $countryName], ['iso2' => null]);
                }

                if (!empty($cityName)) {
                    $city = City::firstOrCreate(['country_id' => $country->id, 'name' => $cityName]);
                    if (!empty($postal)) {
                        PostalCode::firstOrCreate(['country_id' => $country->id, 'city_id' => $city->id, 'postal_code' => $postal]);
                    }
                } elseif (!empty($postal)) {
                    // Some datasets provide postal without city; create postal with null city
                    PostalCode::firstOrCreate(['country_id' => $country->id, 'city_id' => null, 'postal_code' => $postal]);
                }

                $totalImported++;
            }

            $this->info("Imported chunk offset={$offset}, items=" . count($items));
            $offset += $limit;
            // safety: stop if we received fewer than requested (end)
            if (count($items) < $limit) break;
        }

        $this->info('Import finished. Total imported entries: ' . $totalImported);
        return 0;
    }
}
