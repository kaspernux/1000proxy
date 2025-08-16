<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ServerPlanSeeder extends Seeder
{
	public function run(): void
	{
		// If plans already exist, don't duplicate
		if (ServerPlan::query()->exists()) {
			$this->command?->info('Server plans already exist. Skipping ServerPlanSeeder.');
			return;
		}

		$faker = Faker::create();

		$servers = Server::all();
		$brands  = ServerBrand::all();
		$categories = ServerCategory::all();

		if ($servers->isEmpty() || $brands->isEmpty() || $categories->isEmpty()) {
			$this->command?->warn('Missing prerequisite data (servers/brands/categories). Run ServerSeeder, ServerBrandSeeder, ServerCategorySeeder first.');
			return;
		}

		$planTiers = ['Basic', 'Premium', 'Pro'];
		$protocols = ['vless', 'vmess', 'trojan', 'shadowsocks'];

		// Country code mapping helper for nicer data
		$isoMap = [
			'United States' => 'US', 'United Kingdom' => 'GB', 'Germany' => 'DE', 'Japan' => 'JP',
			'Canada' => 'CA', 'France' => 'FR', 'Netherlands' => 'NL', 'Singapore' => 'SG',
		];

		foreach ($servers as $server) {
			$brand = $brands->random();
			// Prefer the server's category if set; otherwise random
			$category = $categories->firstWhere('id', $server->server_category_id) ?? $categories->random();

			// Price baseline by category name
			$basePrice = match($category->name) {
				'Gaming' => $faker->numberBetween(15, 30),
				'Streaming' => $faker->numberBetween(20, 40),
				'Business' => $faker->numberBetween(25, 50),
				'High Security' => $faker->numberBetween(30, 60),
				default => $faker->numberBetween(10, 25),
			};

			foreach ($planTiers as $idx => $tier) {
				$multiplier = 0.7 + ($idx * 0.7); // 0.7, 1.4, 2.1

				$countryName = $server->country ?? $faker->country();
				$countryCode = $isoMap[$countryName] ?? Str::upper(substr(preg_replace('/[^A-Za-z]/','', $countryName), 0, 2));
				$name = "$tier {$category->name} Plan - {$countryName}";
				$slug = Str::slug($name.'-'.$server->id.'-'.Str::random(6));

				ServerPlan::create([
					'server_id' => $server->id,
					'server_brand_id' => $brand->id,
					'server_category_id' => $category->id,
					'country_code' => $countryCode,
					'region' => $faker->state(),
					'protocol' => $faker->randomElement($protocols),
					'bandwidth_mbps' => $faker->numberBetween(50, 1000),
					'supports_ipv6' => $faker->boolean(70),
					'popularity_score' => $faker->numberBetween(1, 100),
					'server_status' => $faker->randomElement(['online', 'offline', 'maintenance']),
					'name' => $name,
					'slug' => $slug,
					'product_image' => 'server-plans/default-plan.png',
					'description' => "{$tier} plan for {$category->name} workloads in {$countryName}.",
					'capacity' => $faker->numberBetween(10, 100),
					'price' => round($basePrice * $multiplier, 2),
					'type' => $faker->randomElement(['single', 'multiple', 'dedicated', 'branded']),
					'days' => $faker->randomElement([7, 30, 90, 365]),
					'volume' => $faker->numberBetween(50, 500),
					'is_active' => true,
					'is_featured' => $faker->boolean(20),
					'is_popular' => $faker->boolean(15),
					'in_stock' => $faker->boolean(90),
					'on_sale' => $faker->boolean(15),
					// Legacy/compat fields populated to reduce deprecations
					'duration_days' => 30,
					'max_connections' => $faker->numberBetween(1, 10),
					'bandwidth_limit_gb' => $faker->numberBetween(10, 500),
				]);
			}
		}

		$this->command?->info('Server plans seeded successfully!');
	}
}

