<?php

namespace Database\Seeders;

use App\Models\ServerReview;
use App\Models\ServerRating;
use App\Models\Server;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ServerReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $servers = Server::all();
        $customers = Customer::all();

        if ($servers->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('No servers or customers found. Please run ServerSeeder and CustomerSeeder first.');
            return;
        }

        $reviewTexts = [
            'Excellent service! Fast speeds and reliable connections.',
            'Great proxy service with minimal downtime. Highly recommended!',
            'Good value for money. Servers are stable and support is responsive.',
            'Fast setup and easy to use. Perfect for my business needs.',
            'Outstanding performance for streaming. No buffering issues.',
            'Professional service with great customer support.',
            'Reliable proxy with consistent speeds throughout the day.',
            'Easy configuration and excellent documentation provided.',
            'Perfect for gaming with low latency connections.',
            'Secure and anonymous browsing experience.',
            'Great for bypassing geo-restrictions.',
            'Stable connection even during peak hours.',
            'User-friendly interface and quick setup process.',
            'Excellent uptime and fast response times.',
            'Good proxy service but could use more server locations.',
            'Decent speed but sometimes experiences minor delays.',
            'Works well but pricing could be more competitive.',
            'Good service overall with room for improvement.',
        ];

        foreach ($servers->take(20) as $server) { // Reviews for first 20 servers
            // Create 3-15 reviews per server
            $reviewCount = $faker->numberBetween(3, 15);

            for ($i = 0; $i < $reviewCount; $i++) {
                $customer = $customers->random();
                $rating = $faker->numberBetween(1, 5);

                // Create rating first
                $serverRating = ServerRating::create([
                    'server_id' => $server->id,
                    'customer_id' => $customer->id,
                    'rating' => $rating,
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
                ]);

                // Create review (some ratings might not have reviews)
                if ($faker->boolean(70)) { // 70% chance of having a review
                    ServerReview::create([
                        'server_id' => $server->id,
                        'customer_id' => $customer->id,
                        'comments' => $faker->randomElement($reviewTexts),
                        'approved' => $faker->boolean(80), // 80% approved
                        'created_at' => $serverRating->created_at,
                        'updated_at' => $serverRating->updated_at,
                    ]);
                }
            }
        }

        $this->command->info('Server reviews and ratings seeded successfully!');
    }
}
