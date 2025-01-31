<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::factory()->count(20)->create();

        Customer::factory()->create([
            'is_active' => true,
            'image' => 'https://via.placeholder.com/640x480.png/00dd77?text=minima',
            'name' => 'Nook Codes',
            'email' => 'nook@1000proxy.bot',
            'password' => bcrypt('Password'), // Use bcrypt to hash the password
            'tgId' => '761184038',
            'refcode' => 'ucy6bU914w',
            'wallet' => 5000.63,
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

    // Use the CustomerFactory to create a specific customer

}