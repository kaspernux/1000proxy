<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Datacenter Proxies',
                'slug' => Str::slug('Datacenter Proxies'),
                'image' => 'server_categories/datacenter.png',
                'is_active' => true,
            ],
            [
                'name' => 'Residential Proxies',
                'slug' => Str::slug('Residential Proxies'),
                'image' => 'server_categories/residential.png',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Proxies',
                'slug' => Str::slug('Mobile Proxies'),
                'image' => 'server_categories/mobile.png',
                'is_active' => true,
            ],
            [
                'name' => 'Rotating Proxies',
                'slug' => Str::slug('Rotating Proxies'),
                'image' => 'server_categories/rotating.png',
                'is_active' => true,
            ],
            [
                'name' => 'Scraping Suite',
                'slug' => Str::slug('Scraping Suite'),
                'image' => 'server_categories/scraping.png',
                'is_active' => true,
            ],
            // Legacy/general purpose categories to preserve prior filtering
            [
                'name' => 'Streaming',
                'slug' => Str::slug('Streaming'),
                'image' => 'server_categories/streaming.png',
                'is_active' => true,
            ],
            [
                'name' => 'Gaming',
                'slug' => Str::slug('Gaming'),
                'image' => 'server_categories/gaming.png',
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'slug' => Str::slug('Business'),
                'image' => 'server_categories/business.png',
                'is_active' => true,
            ],
            [
                'name' => 'High Security',
                'slug' => Str::slug('High Security'),
                'image' => 'server_categories/high-security.png',
                'is_active' => true,
            ],
        ];

        // Use updateOrCreate to avoid duplicate entry errors
        foreach ($categories as $category) {
            \App\Models\ServerCategory::updateOrCreate(
                ['slug' => $category['slug']], // Check by slug
                $category // Update with all data
            );
        }
    }
}
