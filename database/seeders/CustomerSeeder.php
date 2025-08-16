<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Create the specific demo customer or find existing
        $customer = Customer::where('email', 'demo@1000proxy.io')->first();
        if (!$customer) {
            $customer = Customer::create([
                'is_active' => true,
                'image' => 'https://via.placeholder.com/640x480.png/00dd77?text=demo-customer',
                'name' => 'Demo Customer',
                'email' => 'demo@1000proxy.io',
                'password' => bcrypt('D3m0#Cust0mer!2024$'),
                'telegram_chat_id' => '761184038',
                'refcode' => 'DEMO2024',
                'date' => '1990-01-01',
                'phone' => '+1-800-DEMO-001',
                'refered_by' => null,
                'step' => 'completed',
                'freetrial' => 'yes',
                'first_start' => now()->subDays(30),
                'temp' => 25,
                'is_agent' => false,
                'discount_percent' => 10,
                'agent_date' => now()->subDays(15),
                'spam_info' => 'Demo customer account for testing purposes.',
                'email_verified_at' => now(),
            ]);
        }

        // Create a test user for payment system testing
        $testUser = Customer::where('email', 'testuser@1000proxy.io')->first();
        if (!$testUser) {
            $testUser = Customer::create([
                'is_active' => true,
                'image' => 'https://via.placeholder.com/640x480.png/00dd77?text=test-user',
                'name' => 'Test User',
                'email' => 'testuser@1000proxy.io',
                'password' => bcrypt('password123'),
                'telegram_chat_id' => '761184039',
                'refcode' => 'TEST2025',
                'date' => '1995-01-01',
                'phone' => '+1-800-2345-001',
                'refered_by' => null,
                'step' => 'started',
                'freetrial' => 'yes',
                'first_start' => now()->subDays(1),
                'temp' => 20,
                'is_agent' => false,
                'discount_percent' => 0,
                'agent_date' => now()->subDays(1),
                'spam_info' => 'Test user account for payment system testing.',
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('Created demo customer and test user successfully!');
    }
}
