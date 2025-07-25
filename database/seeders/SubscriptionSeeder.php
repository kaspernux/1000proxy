<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Get users who can have subscriptions (not customers, as this is a Cashier subscription)
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found for creating subscriptions.');
            return;
        }

        foreach ($users->take(5) as $user) { // Create subscriptions for first 5 users
            Subscription::create([
                'user_id' => $user->id,
                'name' => $faker->randomElement(['default', 'premium', 'basic']),
                'stripe_id' => 'sub_' . $faker->unique()->lexify('??????????'),
                'stripe_status' => $faker->randomElement(['active', 'trialing', 'past_due', 'canceled', 'unpaid']),
                'stripe_plan' => $faker->randomElement(['plan_basic', 'plan_premium', 'plan_enterprise']),
                'quantity' => $faker->numberBetween(1, 5),
                'trial_ends_at' => $faker->optional()->dateTimeBetween('now', '+30 days'),
                'ends_at' => $faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            ]);
        }

        $this->command->info('Cashier subscriptions seeded successfully!');
    }
}
