<?php

namespace App\Services;

use App\Models\Country;
use App\Models\City;
use App\Models\PostalCode;

class AddressLookupService
{
    public function getCitiesByCountry(string $iso2)
    {
        $country = Country::where('iso2', strtoupper($iso2))->first();
        if (!$country) return collect();
        return $country->cities()->orderBy('name')->get();
    }

    /**
     * Optionally query GeoNames for cities if configured (helpful when DB isn't fully populated).
     */
    public function geonamesCities(string $iso2, int $max = 50)
    {
        $username = config('services.geonames.username') ?: env('GEO_NAMES_USERNAME');
        if (empty($username)) return collect();
        $url = "http://api.geonames.org/searchJSON?country=" . strtoupper($iso2) . "&featureClass=P&maxRows=" . intval($max) . "&username=" . $username;
        try {
            $resp = \Illuminate\Support\Facades\Http::get($url);
            if ($resp->ok()) {
                $data = $resp->json();
                $names = collect($data['geonames'] ?? [])->pluck('name')->unique();
                return $names;
            }
        } catch (\Throwable $e) {
            \Log::debug('GeoNames lookup failed', ['error' => $e->getMessage()]);
        }
        return collect();
    }

    public function getPostalCodesByCity(int $cityId)
    {
        return PostalCode::where('city_id', $cityId)->orderBy('postal_code')->get();
    }

    public function defaultPostalForCountry(string $iso2)
    {
        $country = Country::where('iso2', strtoupper($iso2))->first();
        return $country?->default_postal_code;
    }

    /**
     * Fetch a sample of postal_codes from an Opendatasoft dataset. Returns collection of arrays
     * with keys: country_iso2, country_name, city, postal_code
     */
    public function fetchFromOpendatasoft(string $domain, string $datasetId, int $limit = 100)
    {
        $url = "https://{$domain}/api/explore/v2.1/catalog/datasets/{$datasetId}/records?limit=" . intval($limit);
        try {
            $resp = \Illuminate\Support\Facades\Http::acceptJson()->get($url);
            if (!$resp->ok()) return collect();
            $payload = $resp->json();
            $items = $payload['results'] ?? $payload['records'] ?? [];
            return collect($items)->map(function($it){
                $f = $it['fields'] ?? $it;
                return [
                    'country_iso2' => strtoupper($f['country_code'] ?? ($f['country_code_2'] ?? ($f['country_code_3'] ?? ''))),
                    'country_name' => $f['country'] ?? $f['country_name'] ?? null,
                    'city' => $f['city'] ?? $f['name'] ?? null,
                    'postal_code' => $f['postal_code'] ?? $f['zip'] ?? null,
                ];
            });
        } catch (\Throwable $e) {
            \Log::debug('Opendatasoft fetch failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }
}
