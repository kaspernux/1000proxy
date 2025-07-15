<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@1000proxy.com'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@1000proxy.com',
                'password' => Hash::make('admin123!@#'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create support manager
        User::firstOrCreate(
            ['email' => 'support@1000proxy.com'],
            [
                'name' => 'Support Manager',
                'username' => 'support_manager',
                'email' => 'support@1000proxy.com',
                'password' => Hash::make('support123!@#'),
                'role' => 'support_manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create sales support
        User::firstOrCreate(
            ['email' => 'sales@1000proxy.com'],
            [
                'name' => 'Sales Support',
                'username' => 'sales_support',
                'email' => 'sales@1000proxy.com',
                'password' => Hash::make('sales123!@#'),
                'role' => 'sales_support',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
