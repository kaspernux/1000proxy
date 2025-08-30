This project ships with a minimal countries/cities/postal_codes schema and a small sample seeder.

To provide a full worldwide address database, import one of the following datasets and populate the tables:

- GeoNames (free): http://www.geonames.org/
- OpenAddresses/OpenStreetMap extracts
- Commercial postal code datasets for guaranteed accuracy per country

Recommended approach:
1. Obtain the dataset (CSV/SQL).
2. Map columns to `countries(iso2,name,default_postal_code)`, `cities(country_id,name)`, and `postal_codes(country_id,city_id,postal_code)`.
3. Write a custom importer script (artisan command) which upserts countries first, then cities, then postal codes.
4. Run `php artisan db:seed --class=CountriesSeeder` to create minimal samples, then run your importer for full data.

Note: for high-traffic sites, consider using a dedicated geo/postal service with API and caching.
