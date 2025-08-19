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
        // Create or update default admin user (reset password if exists)
        User::updateOrCreate(
            ['email' => 'admin@1000proxy.io'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'password' => Hash::make('P@ssw0rd!Adm1n2024$'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create or update support manager
        User::updateOrCreate(
            ['email' => 'support@1000proxy.io'],
            [
                'name' => 'Support Manager',
                'username' => 'support_manager',
                'password' => Hash::make('Supp0rt#Mgr!2024&'),
                'role' => 'support_manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create or update manager
        User::updateOrCreate(
            ['email' => 'manager@1000proxy.io'],
            [
                'name' => 'Operations Manager',
                'username' => 'manager',
                'password' => Hash::make('Manag3r!2024$'),
                'role' => 'manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create or update sales support
        User::updateOrCreate(
            ['email' => 'sales@1000proxy.io'],
            [
                'name' => 'Sales Support',
                'username' => 'sales_support',
                'password' => Hash::make('S@les#Team!2024*'),
                'role' => 'sales_support',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create or update analyst
        User::updateOrCreate(
            ['email' => 'analyst@1000proxy.io'],
            [
                'name' => 'Business Analyst',
                'username' => 'analyst',
                'password' => Hash::make('An@lyst!2024%'),
                'role' => 'analyst',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
