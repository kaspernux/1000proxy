<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountriesSeeder extends Seeder
{
    public function run()
    {
        // Minimal sample dataset. For production, import a complete worldwide dataset
        // (e.g., GeoNames, OpenStreetMap extracts or commercial postal datasets) and
        // populate countries, cities, and postal_codes tables.
        Country::updateOrCreate(['iso2' => 'US'], ['name' => 'United States', 'default_postal_code' => '00000']);
        Country::updateOrCreate(['iso2' => 'GB'], ['name' => 'United Kingdom', 'default_postal_code' => 'SW1A 1AA']);
        Country::updateOrCreate(['iso2' => 'NL'], ['name' => 'Netherlands', 'default_postal_code' => '1000']);
    }
}
