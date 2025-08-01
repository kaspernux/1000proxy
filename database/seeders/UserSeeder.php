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
            ['email' => 'admin@1000proxy.io'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@1000proxy.io',
                'password' => Hash::make('P@ssw0rd!Adm1n2024$'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create support manager
        User::firstOrCreate(
            ['email' => 'support@1000proxy.io'],
            [
                'name' => 'Support Manager',
                'username' => 'support_manager',
                'email' => 'support@1000proxy.io',
                'password' => Hash::make('Supp0rt#Mgr!2024&'),
                'role' => 'support_manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create sales support
        User::firstOrCreate(
            ['email' => 'sales@1000proxy.io'],
            [
                'name' => 'Sales Support',
                'username' => 'sales_support',
                'email' => 'sales@1000proxy.io',
                'password' => Hash::make('S@les#Team!2024*'),
                'role' => 'sales_support',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
