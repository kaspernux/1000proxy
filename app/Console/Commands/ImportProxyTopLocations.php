<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Country;
use App\Models\City;
use App\Models\PostalCode;

class ImportProxyTopLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address:import-proxy-top-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a curated list of commonly-used proxy locations into countries/cities/postal_codes (idempotent).';

    public function handle(): int
    {
        // Curated list of commonly used proxy endpoint locations. This is a heuristic starter list.
        // You can expand or replace this with a CSV or Opendatasoft dataset later.
        $locations = [
            ['country_iso' => 'US', 'country' => 'United States', 'city' => 'New York', 'postal' => '10001'],
            ['country_iso' => 'US', 'country' => 'United States', 'city' => 'Los Angeles', 'postal' => '90001'],
            ['country_iso' => 'US', 'country' => 'United States', 'city' => 'Chicago', 'postal' => '60601'],
            ['country_iso' => 'GB', 'country' => 'United Kingdom', 'city' => 'London', 'postal' => 'EC1A'],
            ['country_iso' => 'NL', 'country' => 'Netherlands', 'city' => 'Amsterdam', 'postal' => '1011'],
            ['country_iso' => 'DE', 'country' => 'Germany', 'city' => 'Berlin', 'postal' => '10115'],
            ['country_iso' => 'FR', 'country' => 'France', 'city' => 'Paris', 'postal' => '75001'],
            ['country_iso' => 'RU', 'country' => 'Russia', 'city' => 'Moscow', 'postal' => '101000'],
            ['country_iso' => 'IN', 'country' => 'India', 'city' => 'Mumbai', 'postal' => '400001'],
            ['country_iso' => 'BR', 'country' => 'Brazil', 'city' => 'Sao Paulo', 'postal' => '01000-000'],
            ['country_iso' => 'CA', 'country' => 'Canada', 'city' => 'Toronto', 'postal' => 'M5H'],
            ['country_iso' => 'JP', 'country' => 'Japan', 'city' => 'Tokyo', 'postal' => '100-0001'],
            ['country_iso' => 'SG', 'country' => 'Singapore', 'city' => 'Singapore', 'postal' => '018989'],
            ['country_iso' => 'ES', 'country' => 'Spain', 'city' => 'Madrid', 'postal' => '28001'],
            ['country_iso' => 'IT', 'country' => 'Italy', 'city' => 'Milan', 'postal' => '20121'],
            ['country_iso' => 'TR', 'country' => 'Turkey', 'city' => 'Istanbul', 'postal' => '34000'],
            ['country_iso' => 'PL', 'country' => 'Poland', 'city' => 'Warsaw', 'postal' => '00-001'],
            ['country_iso' => 'SE', 'country' => 'Sweden', 'city' => 'Stockholm', 'postal' => '100 12'],
            ['country_iso' => 'CH', 'country' => 'Switzerland', 'city' => 'Zurich', 'postal' => '8001'],
            ['country_iso' => 'AU', 'country' => 'Australia', 'city' => 'Sydney', 'postal' => '2000'],
            ['country_iso' => 'KR', 'country' => 'South Korea', 'city' => 'Seoul', 'postal' => '04524'],
            ['country_iso' => 'ZA', 'country' => 'South Africa', 'city' => 'Johannesburg', 'postal' => '2001'],
            ['country_iso' => 'MX', 'country' => 'Mexico', 'city' => 'Mexico City', 'postal' => '01000'],
            ['country_iso' => 'AR', 'country' => 'Argentina', 'city' => 'Buenos Aires', 'postal' => 'C1002'],
            ['country_iso' => 'CL', 'country' => 'Chile', 'city' => 'Santiago', 'postal' => '8320000'],
            ['country_iso' => 'CO', 'country' => 'Colombia', 'city' => 'Bogota', 'postal' => '110111'],
            ['country_iso' => 'AE', 'country' => 'United Arab Emirates', 'city' => 'Dubai', 'postal' => '00000'],
            ['country_iso' => 'IL', 'country' => 'Israel', 'city' => 'Tel Aviv', 'postal' => '61000'],
            ['country_iso' => 'HK', 'country' => 'Hong Kong', 'city' => 'Hong Kong', 'postal' => '999077'],
            ['country_iso' => 'ID', 'country' => 'Indonesia', 'city' => 'Jakarta', 'postal' => '10110'],
            ['country_iso' => 'MY', 'country' => 'Malaysia', 'city' => 'Kuala Lumpur', 'postal' => '50000'],
            ['country_iso' => 'VN', 'country' => 'Vietnam', 'city' => 'Ho Chi Minh City', 'postal' => '700000'],
            ['country_iso' => 'TH', 'country' => 'Thailand', 'city' => 'Bangkok', 'postal' => '10100'],
            ['country_iso' => 'NG', 'country' => 'Nigeria', 'city' => 'Lagos', 'postal' => '100001'],
            ['country_iso' => 'EG', 'country' => 'Egypt', 'city' => 'Cairo', 'postal' => '11511'],
            ['country_iso' => 'PK', 'country' => 'Pakistan', 'city' => 'Karachi', 'postal' => '74000'],
            ['country_iso' => 'BD', 'country' => 'Bangladesh', 'city' => 'Dhaka', 'postal' => '1000'],
            ['country_iso' => 'RU', 'country' => 'Russia', 'city' => 'Moscow', 'postal' => '101000'],
            ['country_iso' => 'RO', 'country' => 'Romania', 'city' => 'Bucharest', 'postal' => '010011'],
            ['country_iso' => 'CZ', 'country' => 'Czechia', 'city' => 'Prague', 'postal' => '110 00'],
            ['country_iso' => 'BE', 'country' => 'Belgium', 'city' => 'Brussels', 'postal' => '1000'],
            ['country_iso' => 'PT', 'country' => 'Portugal', 'city' => 'Lisbon', 'postal' => '1100-001'],
            ['country_iso' => 'AT', 'country' => 'Austria', 'city' => 'Vienna', 'postal' => '1010'],
            ['country_iso' => 'DK', 'country' => 'Denmark', 'city' => 'Copenhagen', 'postal' => '1050'],
            ['country_iso' => 'NO', 'country' => 'Norway', 'city' => 'Oslo', 'postal' => '0150'],
            ['country_iso' => 'FI', 'country' => 'Finland', 'city' => 'Helsinki', 'postal' => '00100'],
            ['country_iso' => 'GR', 'country' => 'Greece', 'city' => 'Athens', 'postal' => '10555'],
            ['country_iso' => 'IE', 'country' => 'Ireland', 'city' => 'Dublin', 'postal' => 'D01'],
            ['country_iso' => 'NZ', 'country' => 'New Zealand', 'city' => 'Auckland', 'postal' => '1010'],
            ['country_iso' => 'PH', 'country' => 'Philippines', 'city' => 'Manila', 'postal' => '1000'],
            ['country_iso' => 'SA', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'postal' => '11564'],
            ['country_iso' => 'KZ', 'country' => 'Kazakhstan', 'city' => 'Almaty', 'postal' => '050000'],
            ['country_iso' => 'PE', 'country' => 'Peru', 'city' => 'Lima', 'postal' => '15001'],
            ['country_iso' => 'VE', 'country' => 'Venezuela', 'city' => 'Caracas', 'postal' => '1010'],
        ];

        $this->info('Starting import of ' . count($locations) . ' proxy locations...');

        $countryCount = $cityCount = $postalCount = 0;

        foreach ($locations as $row) {
            $country = Country::firstOrCreate(
                ['iso2' => strtoupper($row['country_iso'])],
                ['name' => $row['country'] ?? $row['country_iso']]
            );

            $countryCount += $country->wasRecentlyCreated ? 1 : 0;

            $city = City::firstOrCreate(
                ['country_id' => $country->id, 'name' => $row['city']],
                ['name' => $row['city']]
            );

            $cityCount += $city->wasRecentlyCreated ? 1 : 0;

            if (!empty($row['postal'])) {
                $postal = PostalCode::firstOrCreate(
                    ['country_id' => $country->id, 'city_id' => $city->id, 'postal_code' => $row['postal']],
                    ['postal_code' => $row['postal']]
                );

                $postalCount += $postal->wasRecentlyCreated ? 1 : 0;
            }
        }

        $this->info("Imported/updated: countries={$countryCount}, cities={$cityCount}, postalcodes={$postalCount}");

        return 0;
    }
}
