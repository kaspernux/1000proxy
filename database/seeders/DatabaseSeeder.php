<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'username'=>'Grambot',
            'email' => 'admin@admin.com',
            'password' => Hash::make('Admin123'),
        ]);

        // Create Customer
        $this->call([
            CustomerSeeder::class,
            // other seeders
        ]);

        $this->call([
            ServerCategorySeeder::class,
            // Add other seeders here
        ]);

        $this->call([
            ServerBrandSeeder::class,
            // Add other seeders here
        ]);

        $this->call([
            PaymentMethodSeeder::class,
            // Add other seeders here
        ]);




    }
}
