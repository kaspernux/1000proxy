<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\City;
use App\Models\PostalCode;

class AddressImportOpendatasoftBulk extends Command
{
    protected $signature = 'address:import-opendatasoft-bulk {--manifest= : Path to CSV manifest (domain,dataset_id,iso2,country,city,postal,limit)}';
    protected $description = 'Bulk import multiple Opendatasoft datasets using a manifest CSV. Each row: domain,dataset_id,iso2,country,city,postal,limit';

    public function handle()
    {
        $manifestPath = $this->option('manifest');
        if (empty($manifestPath)) {
            $this->error('Please provide --manifest=path/to/manifest.csv');
            return 1;
        }

        if (!file_exists($manifestPath) || !is_readable($manifestPath)) {
            $this->error('Manifest file not found or not readable: ' . $manifestPath);
            return 1;
        }

        $this->info('Reading manifest: ' . $manifestPath);

        $handle = fopen($manifestPath, 'r');
        if ($handle === false) {
            $this->error('Failed to open manifest file');
            return 1;
        }

        $rowNum = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            // skip empty lines
            if (count($row) === 0) continue;
            // allow header skip if header contains 'domain'
            if ($rowNum === 1 && stripos(implode(',', $row), 'domain') !== false) {
                $this->info('Skipping header row');
                continue;
            }

            // expected columns: domain,dataset_id,iso2,country,city,postal,limit
            $domain = $row[0] ?? null;
            $dataset = $row[1] ?? null;
            $iso2 = $row[2] ?? '';
            $country = $row[3] ?? '';
            $city = $row[4] ?? '';
            $postal = $row[5] ?? '';
            $limit = $row[6] ?? null;

            if (empty($domain) || empty($dataset)) {
                $this->warn("Skipping row {$rowNum}: domain or dataset missing");
                continue;
            }

            $this->info("Importing row {$rowNum}: {$domain} / {$dataset}");


            // only include option-type params here; domain and dataset are passed positionally
            $params = [
                '--iso2' => $iso2 ?: 'country_code',
                '--country' => $country ?: 'country',
                '--city' => $city ?: 'city',
                '--postal' => $postal ?: 'postal_code',
            ];

            if (!empty($limit)) {
                $params['--limit'] = $limit;
            }

            // Inline import logic (paged) using provided field mappings
            $iso2Field = $params['--iso2'] ?? 'country_code';
            $countryField = $params['--country'] ?? 'country';
            $cityField = $params['--city'] ?? 'city';
            $postalField = $params['--postal'] ?? 'postal_code';
            $limitVal = $params['--limit'] ?? 1000;

            $offset = 0;
            $imported = 0;

            try {
                while (true) {
                    $url = "https://{$domain}/api/explore/v2.1/catalog/datasets/{$dataset}/records?limit={$limitVal}&offset={$offset}";
                    $resp = Http::acceptJson()->get($url);
                    if (!$resp->ok()) {
                        $this->error('Request failed: HTTP ' . $resp->status() . ' for ' . $url);
                        break;
                    }

                    $payload = $resp->json();
                    $items = $payload['results'] ?? $payload['records'] ?? $payload['data'] ?? [];
                    if (empty($items)) break;

                    foreach ($items as $item) {
                        $fields = $item['fields'] ?? $item;
                        $iso2 = strtoupper(trim($fields[$iso2Field] ?? ($fields['country_code'] ?? '')));
                        $countryName = trim($fields[$countryField] ?? ($fields['country'] ?? ''));
                        $cityName = trim($fields[$cityField] ?? ($fields['city'] ?? ''));
                        $postal = trim($fields[$postalField] ?? ($fields['postal_code'] ?? ''));

                        if (empty($iso2) && empty($countryName)) continue;

                        if (!empty($iso2)) {
                            $country = Country::firstOrCreate(['iso2' => $iso2], ['name' => $countryName ?: $iso2]);
                        } else {
                            $country = Country::firstOrCreate(['name' => $countryName], ['iso2' => null]);
                        }

                        if (!empty($cityName)) {
                            $city = City::firstOrCreate(['country_id' => $country->id, 'name' => $cityName]);
                            if (!empty($postal)) {
                                PostalCode::firstOrCreate(['country_id' => $country->id, 'city_id' => $city->id, 'postal_code' => $postal]);
                            }
                        } elseif (!empty($postal)) {
                            PostalCode::firstOrCreate(['country_id' => $country->id, 'city_id' => null, 'postal_code' => $postal]);
                        }

                        $imported++;
                    }

                    $this->info("Imported chunk offset={$offset}, items=" . count($items));
                    $offset += $limitVal;
                    if (count($items) < $limitVal) break;
                }

                $this->info('Finished import for row ' . $rowNum . '. total items processed: ' . $imported);
            } catch (\Throwable $e) {
                $this->error('Importer failed for row ' . $rowNum . ': ' . $e->getMessage());
            }
        }

        fclose($handle);

        $this->info('Bulk import finished');
        return 0;
    }
}
