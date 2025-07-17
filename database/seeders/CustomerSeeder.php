<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Create 20 random customers
        Customer::factory()->count(20)->create();

        // Create the specific test customer or find existing
        $customer = Customer::where('email', 'demo@1000proxy.io')->first();

        if (!$customer) {
            $customer = Customer::factory()->create([
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
                'step' => 9,
                'freetrial' => true,
                'first_start' => now()->subDays(30),
                'temp' => 25,
                'is_agent' => false,
                'discount_percent' => 10,
                'agent_date' => now()->subDays(15),
                'spam_info' => 'Demo customer account for testing purposes.',
            ]);
        }

        $this->command->info('Created customers successfully!');
    }
}
