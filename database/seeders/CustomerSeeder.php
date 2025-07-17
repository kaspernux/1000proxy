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
        $customer = Customer::where('email', 'nook@1000proxy.io')->first();

        if (!$customer) {
            $customer = Customer::factory()->create([
                'is_active' => true,
                'image' => 'https://via.placeholder.com/640x480.png/00dd77?text=minima',
                'name' => 'Nook Codes',
                'email' => 'nook@1000proxy.io',
                'password' => bcrypt('Password'),
                'telegram_chat_id' => '761184038',
                'refcode' => 'ucy6bU914w',
                'date' => '1995-01-30',
                'phone' => '+15809581739',
                'refered_by' => null,
                'step' => 9,
                'freetrial' => false,
                'first_start' => '2021-02-23 05:57:52',
                'temp' => 50,
                'is_agent' => false,
                'discount_percent' => 0,
                'agent_date' => '2002-12-24 08:02:21',
                'spam_info' => 'Consectetur molestias praesentium ut quasi et cum ut.',
            ]);
        }

        $this->command->info('Created customers successfully!');
    }
}
