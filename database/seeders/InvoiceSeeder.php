<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use Faker\Factory as Faker;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get existing orders, customers, and payment methods
        $orders = Order::all();
        $customers = Customer::all();
        $paymentMethods = PaymentMethod::all();

        if ($orders->isEmpty() || $customers->isEmpty() || $paymentMethods->isEmpty()) {
            $this->command->warn('Cannot seed invoices without orders, customers, and payment methods.');
            return;
        }

        // Create 50 invoices for existing orders
        foreach ($orders->take(50) as $order) {
            $paymentMethod = $paymentMethods->random();
            $priceAmount = $faker->randomFloat(2, 10, 500);
            $paymentStatus = $faker->randomElement(['pending', 'paid', 'failed', 'partially_paid', 'expired']);

            Invoice::create([
                'iid' => 'INV-' . strtoupper($faker->lexify('?????')),
                'customer_id' => $order->customer_id,
                'payment_method_id' => $paymentMethod->id,
                'payment_id' => $faker->uuid(),
                'payment_status' => $paymentStatus,
                'pay_address' => $paymentMethod->name === 'Bitcoin' ? $faker->regexify('[13][a-km-zA-HJ-NP-Z1-9]{25,34}') :
                               ($paymentMethod->name === 'Ethereum' ? '0x' . $faker->regexify('[a-fA-F0-9]{40}') : $faker->email()),
                'price_amount' => $priceAmount,
                'price_currency' => 'USD',
                'pay_amount' => $paymentStatus === 'paid' ? $priceAmount :
                              ($paymentStatus === 'partially_paid' ? $priceAmount * 0.7 : null),
                'pay_currency' => $paymentMethod->currency ?? 'USD',
                'order_id' => $order->id,
                'order_description' => 'Proxy Service - Order #' . $order->id,
                'ipn_callback_url' => 'https://1000proxy.io/ipn/callback',
                'invoice_url' => 'https://1000proxy.io/invoice/' . $faker->uuid(),
                'success_url' => 'https://1000proxy.io/payment/success',
                'cancel_url' => 'https://1000proxy.io/payment/cancel',
                'partially_paid_url' => 'https://1000proxy.io/payment/partially-paid',
                'purchase_id' => 'PUR-' . strtoupper($faker->lexify('?????')),
                'amount_received' => $paymentStatus === 'paid' ? $priceAmount :
                                   ($paymentStatus === 'partially_paid' ? $priceAmount * 0.7 : 0),
                'payin_extra_id' => $faker->optional()->numerify('########'),
                'smart_contract' => $paymentMethod->name === 'Ethereum' ? '0x' . $faker->regexify('[a-fA-F0-9]{40}') : null,
                'network' => $paymentMethod->name === 'Bitcoin' ? 'bitcoin' :
                            ($paymentMethod->name === 'Ethereum' ? 'ethereum' : null),
                'network_precision' => $paymentMethod->name === 'Bitcoin' ? 8 :
                                     ($paymentMethod->name === 'Ethereum' ? 18 : null),
                'time_limit' => $faker->numberBetween(1800, 7200), // 30 minutes to 2 hours
                'expiration_estimate_date' => $faker->dateTimeBetween('now', '+2 hours'),
                'is_fixed_rate' => $faker->boolean(70),
                'is_fee_paid_by_user' => $faker->boolean(80),
                'valid_until' => $faker->dateTimeBetween('now', '+24 hours'),
                'type' => $faker->randomElement(['invoice', 'subscription', 'donation']),
                'redirect_url' => 'https://1000proxy.io/account',
                'created_at' => $faker->dateTimeBetween('-30 days', 'now'),
                'updated_at' => $faker->dateTimeBetween('-30 days', 'now'),
            ]);
        }

        $this->command->info('Created 50 invoices successfully.');
    }
}
