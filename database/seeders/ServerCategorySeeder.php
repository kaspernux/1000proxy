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

        DB::table('server_categories')->insert($categories);
    }
}