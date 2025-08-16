<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\ServerPlan;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $customers = Customer::all();
        $serverPlans = ServerPlan::all();
        $paymentMethods = PaymentMethod::all();

        if ($customers->isEmpty() || $serverPlans->isEmpty()) {
            $this->command->warn('No customers or server plans found. Please run CustomerSeeder and ServerSeeder first.');
            return;
        }

    foreach ($customers->take(20) as $customer) {
            // Create 1-3 orders per customer
            $orderCount = $faker->numberBetween(1, 3);

            for ($i = 0; $i < $orderCount; $i++) {
                $grandAmount = $faker->randomFloat(2, 10, 500);

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'grand_amount' => $grandAmount,
                    'currency' => 'USD',
                    'payment_method' => $paymentMethods->isNotEmpty() ? $paymentMethods->random()->id : null,
                    'payment_status' => $faker->randomElement(['pending', 'paid', 'failed']),
                    'order_status' => $faker->randomElement(['new', 'processing', 'completed', 'dispute']),
                    'payment_invoice_url' => $faker->optional()->url(),
                    'notes' => $faker->optional()->sentence(),
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
                ]);

                // Create 1-3 order items per order
                $itemCount = $faker->numberBetween(1, 3);
                $subtotal = 0;

                for ($j = 0; $j < $itemCount; $j++) {
                    $serverPlan = $serverPlans->random();
                    $quantity = $faker->numberBetween(1, 3);
                    $unitAmount = $serverPlan->price ?? $faker->randomFloat(2, 5, 100);
                    $totalAmount = $unitAmount * $quantity;
                    $subtotal += $totalAmount;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'server_plan_id' => $serverPlan->id,
                        'server_id' => $serverPlan->server_id ?? null,
                        'quantity' => $quantity,
                        'unit_amount' => $unitAmount,
                        'total_amount' => $totalAmount,
                        'agent_bought' => $faker->boolean(20),
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                    ]);
                }
            }
        }

        $this->command->info('Orders and order items seeded successfully!');
    }
}
