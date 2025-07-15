<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use Illuminate\Database\Seeder;

class ServerPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure brands and categories exist
        $brands = ServerBrand::all();
        $categories = ServerCategory::all();
        $servers = Server::all();

        if ($brands->isEmpty() || $categories->isEmpty() || $servers->isEmpty()) {
            $this->command->warn('Please run ServerBrandSeeder, ServerCategorySeeder, and ensure servers exist first.');
            return;
        }

        $plans = [
            // US Gaming Plans
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'proxy-titan')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'gaming')->first()?->id ?? $categories->first()->id,
                'name' => 'US Gaming Pro',
                'slug' => 'us-gaming-pro',
                'description' => 'Ultra-low latency gaming proxy optimized for US servers',
                'price' => 15.99,
                'data_limit_gb' => 100,
                'days' => 30,
                'country_code' => 'US',
                'region' => 'East Coast',
                'protocol' => 'vless',
                'bandwidth_mbps' => 1000,
                'supports_ipv6' => true,
                'popularity_score' => 95,
                'server_status' => 'online',
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'max_clients' => 100,
                'current_clients' => 45,
                'auto_provision' => true,
            ],
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'shield-proxy')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'gaming')->first()?->id ?? $categories->first()->id,
                'name' => 'US Gaming Basic',
                'slug' => 'us-gaming-basic',
                'description' => 'Affordable gaming proxy for casual gamers',
                'price' => 8.99,
                'data_limit_gb' => 50,
                'days' => 30,
                'country_code' => 'US',
                'region' => 'West Coast',
                'protocol' => 'vmess',
                'bandwidth_mbps' => 500,
                'supports_ipv6' => false,
                'popularity_score' => 78,
                'server_status' => 'online',
                'is_active' => true,
                'in_stock' => true,
                'max_clients' => 150,
                'current_clients' => 89,
                'auto_provision' => true,
            ],

            // UK Streaming Plans
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'stealth-net')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'streaming')->first()?->id ?? $categories->first()->id,
                'name' => 'UK Streaming Premium',
                'slug' => 'uk-streaming-premium',
                'description' => 'High-bandwidth streaming proxy for 4K content',
                'price' => 19.99,
                'data_limit_gb' => 200,
                'days' => 30,
                'country_code' => 'GB',
                'region' => 'London',
                'protocol' => 'vless',
                'bandwidth_mbps' => 2000,
                'supports_ipv6' => true,
                'popularity_score' => 92,
                'server_status' => 'online',
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'max_clients' => 80,
                'current_clients' => 23,
                'auto_provision' => true,
            ],
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'guardian-proxy')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'streaming')->first()?->id ?? $categories->first()->id,
                'name' => 'UK Streaming Standard',
                'slug' => 'uk-streaming-standard',
                'description' => 'Reliable streaming proxy for HD content',
                'price' => 12.99,
                'data_limit_gb' => 100,
                'days' => 30,
                'country_code' => 'GB',
                'region' => 'Manchester',
                'protocol' => 'trojan',
                'bandwidth_mbps' => 1000,
                'supports_ipv6' => true,
                'popularity_score' => 85,
                'server_status' => 'online',
                'is_active' => true,
                'in_stock' => true,
                'max_clients' => 120,
                'current_clients' => 67,
                'auto_provision' => true,
            ],

            // Germany Business Plans
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'guardian-proxy')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'business')->first()?->id ?? $categories->first()->id,
                'name' => 'DE Business Enterprise',
                'slug' => 'de-business-enterprise',
                'description' => 'Enterprise-grade security for business applications',
                'price' => 29.99,
                'data_limit_gb' => 500,
                'days' => 30,
                'country_code' => 'DE',
                'region' => 'Frankfurt',
                'protocol' => 'vless',
                'bandwidth_mbps' => 1500,
                'supports_ipv6' => true,
                'popularity_score' => 88,
                'server_status' => 'online',
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'max_clients' => 50,
                'current_clients' => 12,
                'auto_provision' => true,
            ],

            // Japan High Security Plans
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'shield-proxy')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'high-security')->first()?->id ?? $categories->first()->id,
                'name' => 'JP Security Max',
                'slug' => 'jp-security-max',
                'description' => 'Maximum security proxy with advanced encryption',
                'price' => 24.99,
                'data_limit_gb' => 150,
                'days' => 30,
                'country_code' => 'JP',
                'region' => 'Tokyo',
                'protocol' => 'shadowsocks',
                'bandwidth_mbps' => 800,
                'supports_ipv6' => true,
                'popularity_score' => 90,
                'server_status' => 'online',
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'max_clients' => 60,
                'current_clients' => 34,
                'auto_provision' => true,
            ],

            // Canada Mixed Protocol Plans
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'stealth-net')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'business')->first()?->id ?? $categories->first()->id,
                'name' => 'CA Multi-Protocol',
                'slug' => 'ca-multi-protocol',
                'description' => 'Flexible proxy supporting multiple protocols',
                'price' => 16.99,
                'data_limit_gb' => 120,
                'days' => 30,
                'country_code' => 'CA',
                'region' => 'Toronto',
                'protocol' => 'mixed',
                'bandwidth_mbps' => 1200,
                'supports_ipv6' => true,
                'popularity_score' => 82,
                'server_status' => 'online',
                'is_active' => true,
                'in_stock' => true,
                'max_clients' => 90,
                'current_clients' => 56,
                'auto_provision' => true,
            ],

            // Budget Plans
            [
                'server_id' => $servers->first()->id,
                'server_brand_id' => $brands->where('slug', 'proxy-titan')->first()?->id ?? $brands->first()->id,
                'server_category_id' => $categories->where('slug', 'streaming')->first()?->id ?? $categories->first()->id,
                'name' => 'Budget Streaming',
                'slug' => 'budget-streaming',
                'description' => 'Affordable streaming proxy for budget-conscious users',
                'price' => 5.99,
                'data_limit_gb' => 25,
                'days' => 30,
                'country_code' => 'US',
                'region' => 'Central',
                'protocol' => 'vmess',
                'bandwidth_mbps' => 300,
                'supports_ipv6' => false,
                'popularity_score' => 70,
                'server_status' => 'online',
                'is_active' => true,
                'in_stock' => true,
                'max_clients' => 200,
                'current_clients' => 180,
                'auto_provision' => true,
            ],
        ];

        foreach ($plans as $plan) {
            ServerPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Created ' . count($plans) . ' server plans with advanced filtering data.');
    }
}
