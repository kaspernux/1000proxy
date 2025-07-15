<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    public function run(): void
    {
        // Get brands and categories
        $brands = ServerBrand::all();
        $categories = ServerCategory::all();

        if ($brands->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Please run ServerBrandSeeder and ServerCategorySeeder first');
            return;
        }

        // Create servers across different countries
        $countries = ['United States', 'United Kingdom', 'Germany', 'Japan', 'Canada', 'France', 'Netherlands', 'Singapore'];

        foreach ($countries as $country) {
            foreach ($brands->take(2) as $brand) { // 2 brands per country
                foreach ($categories->take(2) as $category) { // 2 categories per brand
                    $server = Server::create([
                        'name' => $brand->name . ' ' . $country . ' ' . $category->name . ' Server',
                        'server_brand_id' => $brand->id,
                        'server_category_id' => $category->id,
                        'host' => fake()->ipv4(),
                        'panel_port' => 2053,
                        'username' => 'admin',
                        'password' => 'secure_password',
                        'web_base_path' => '/panel',
                        'country' => $country,
                        'description' => "High-performance proxy server located in $country. Optimized for speed and reliability.",
                        'status' => fake()->randomElement(['up', 'down', 'paused']),
                        'ip' => fake()->ipv4(),
                        'port' => 443,
                        'is_active' => '1',
                    ]);

                // Create server plans for each category
                foreach ($categories as $planCategory) {
                    $basePrice = match($planCategory->name) {
                        'Gaming' => fake()->numberBetween(15, 30),
                        'Streaming' => fake()->numberBetween(20, 40),
                        'Business' => fake()->numberBetween(25, 50),
                        'High Security' => fake()->numberBetween(30, 60),
                        default => fake()->numberBetween(10, 25),
                    };

                    // Create multiple plans per category
                    $planTypes = ['Basic', 'Premium', 'Pro'];
                    foreach ($planTypes as $index => $planType) {
                        $multiplier = ($index + 1) * 0.7; // Basic = 0.7x, Premium = 1.4x, Pro = 2.1x

                        $uniqueSlug = str()->slug("$planType {$planCategory->name} Plan {$country} {$brand->name} " . fake()->uuid());

                        ServerPlan::create([
                            'name' => "$planType {$planCategory->name} Plan - {$country}",
                            'slug' => $uniqueSlug,
                            'server_id' => $server->id,
                            'server_brand_id' => $brand->id,
                            'server_category_id' => $planCategory->id,
                            'country_code' => substr(str_replace(' ', '', $country), 0, 2), // Simple country code
                            'region' => fake()->state(),
                            'protocol' => fake()->randomElement(['vless', 'vmess', 'trojan', 'shadowsocks']),
                            'bandwidth_mbps' => fake()->numberBetween(50, 500),
                            'supports_ipv6' => fake()->boolean(70),
                            'server_status' => fake()->randomElement(['online', 'offline', 'maintenance']),
                            'description' => "Perfect $planType plan for {$planCategory->name} activities in $country. Powered by {$brand->name}.",
                            'price' => round($basePrice * $multiplier, 2),
                            'type' => fake()->randomElement(['single', 'multiple', 'dedicated', 'branded']),
                            'days' => fake()->randomElement([7, 30, 90, 365]),
                            'volume' => fake()->numberBetween(50, 500), // GB
                            'capacity' => fake()->numberBetween(10, 100),
                            'is_active' => true,
                            'is_featured' => fake()->boolean(20), // 20% chance of being featured
                            'on_sale' => fake()->boolean(15), // 15% chance of being on sale
                            'popularity_score' => fake()->numberBetween(1, 100),
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
