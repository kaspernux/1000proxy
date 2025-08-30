<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use App\Models\Country;
use App\Models\City;
use App\Models\PostalCode;

class AddressImportCsv extends Command
{
    protected $signature = 'address:import-csv {file} {--has-header}';
    protected $description = 'Import countries,cities,postal_codes from a CSV file. CSV should have columns: iso2,country,city,postal_code';

    public function handle(Filesystem $fs)
    {
        $path = $this->argument('file');
        if (!$fs->exists($path)) {
            $this->error('File not found: ' . $path);
            return 1;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error('Failed to open file: ' . $path);
            return 1;
        }

        $row = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if ($row === 1 && $this->option('has-header')) continue;
            // Expecting at least 4 columns: iso2,country,city,postal_code
            $iso2 = strtoupper(trim($data[0] ?? ''));
            $countryName = trim($data[1] ?? '');
            $cityName = trim($data[2] ?? '');
            $postal = trim($data[3] ?? '');

            if (empty($iso2) || empty($countryName)) continue;

            $country = Country::firstOrCreate(['iso2' => $iso2], ['name' => $countryName]);
            if (!empty($cityName)) {
                $city = City::firstOrCreate(['country_id' => $country->id, 'name' => $cityName]);
                if (!empty($postal)) {
                    PostalCode::firstOrCreate(['country_id' => $country->id, 'city_id' => $city->id, 'postal_code' => $postal]);
                }
            }
        }
        fclose($handle);
        $this->info('Import completed.');
        return 0;
    }
}
