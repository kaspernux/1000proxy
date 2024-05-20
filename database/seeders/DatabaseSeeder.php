<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('Admin123'),
        ]);

        // Create a regular user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@email.com',
            'password' => Hash::make('Userd123'),
        ]);
    }
}