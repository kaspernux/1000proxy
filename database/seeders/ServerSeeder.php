<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class ServerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Get brands and categories
        $brands = ServerBrand::all();
        $categories = ServerCategory::all();

        if ($brands->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Please run ServerBrandSeeder and ServerCategorySeeder first');
            return;
        }

        // Create servers across different countries (full names)
        $countries = ['United States', 'United Kingdom', 'Germany', 'Japan', 'Canada', 'France', 'Netherlands', 'Singapore'];
        // Proper ISO 3166-1 alpha-2 codes mapping
        $countryIsoMap = [
            'United States' => 'US',
            'United Kingdom' => 'GB', // Use GB (official); previously we derived "UN" which is invalid
            'Germany' => 'DE',
            'Japan' => 'JP',
            'Canada' => 'CA',
            'France' => 'FR',
            'Netherlands' => 'NL',
            'Singapore' => 'SG',
        ];

        foreach ($countries as $country) {
            foreach ($brands->take(2) as $brand) { // 2 brands per country
                foreach ($categories->take(2) as $category) { // 2 categories per brand
                    $server = Server::create([
                        'name' => $brand->name . ' ' . $country . ' ' . $category->name . ' Server',
                        'server_brand_id' => $brand->id,
                        'server_category_id' => $category->id,
                        'host' => $faker->ipv4(),
                        'panel_port' => 2053,
                        'username' => 'admin',
                        'password' => 'secure_password',
                        'web_base_path' => '/panel',
                        'country' => $country,
                        'description' => "High-performance proxy server located in $country. Optimized for speed and reliability.",
                        'status' => $faker->randomElement(['up', 'down', 'paused']),
                        'ip' => $faker->ipv4(),
                        'port' => 443,
                        'is_active' => '1',
                    ]);

                // Create server plans for each category
                foreach ($categories as $planCategory) {
                    $basePrice = match($planCategory->name) {
                        'Gaming' => $faker->numberBetween(15, 30),
                        'Streaming' => $faker->numberBetween(20, 40),
                        'Business' => $faker->numberBetween(25, 50),
                        'High Security' => $faker->numberBetween(30, 60),
                        default => $faker->numberBetween(10, 25),
                    };

                    // Create multiple plans per category
                    $planTypes = ['Basic', 'Premium', 'Pro'];
                    foreach ($planTypes as $index => $planType) {
                        $multiplier = ($index + 1) * 0.7; // Basic = 0.7x, Premium = 1.4x, Pro = 2.1x

                        $uniqueSlug = str()->slug("$planType {$planCategory->name} Plan {$country} {$brand->name} " . $faker->uuid());

                        ServerPlan::create([
                            'name' => "$planType {$planCategory->name} Plan - {$country}",
                            'slug' => $uniqueSlug,
                            'server_id' => $server->id,
                            'server_brand_id' => $brand->id,
                            'server_category_id' => $planCategory->id,
                            // Use proper ISO2 code; fallback to best-effort first 2 letters if unmapped
                            'country_code' => $countryIsoMap[$country] ?? Str::upper(substr(preg_replace('/[^A-Za-z]/','',$country), 0, 2)),
                            'region' => $faker->state(),
                            'protocol' => $faker->randomElement(['vless', 'vmess', 'trojan', 'shadowsocks']),
                            'bandwidth_mbps' => $faker->numberBetween(50, 500),
                            'supports_ipv6' => $faker->boolean(70),
                            'server_status' => $faker->randomElement(['online', 'offline', 'maintenance']),
                            'description' => "Perfect $planType plan for {$planCategory->name} activities in $country. Powered by {$brand->name}.",
                            'price' => round($basePrice * $multiplier, 2),
                            'type' => $faker->randomElement(['single', 'multiple', 'dedicated', 'branded']),
                            'days' => $faker->randomElement([7, 30, 90, 365]),
                            'volume' => $faker->numberBetween(50, 500), // GB
                            'capacity' => $faker->numberBetween(10, 100),
                            'is_active' => true,
                            'is_featured' => $faker->boolean(20), // 20% chance of being featured
                            'on_sale' => $faker->boolean(15), // 15% chance of being on sale
                            'popularity_score' => $faker->numberBetween(1, 100),
                            'product_image' => 'server-plans/default-plan.png',
                        ]);
                    }
                }
                }
            }
        }

        $this->command->info('Created servers and server plans successfully!');
    }
}
