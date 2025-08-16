<?php

namespace Database\Seeders;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please run CustomerSeeder first.');
            return;
        }

        foreach ($customers as $customer) {
            // Create wallet for each customer
        $wallet = Wallet::firstOrCreate(
                ['customer_id' => $customer->id],
                [
            'balance' => $faker->randomFloat(2, 0, 500),
            'currency' => 'USD',
                    'btc_address' => $faker->regexify('[13][a-km-zA-HJ-NP-Z1-9]{25,34}'),
                    'xmr_address' => $faker->regexify('4[0-9AB][1-9A-HJ-NP-Za-km-z]{93}'),
                    'sol_address' => $faker->regexify('[1-9A-HJ-NP-Za-km-z]{32,44}'),
                    'btc_qr' => 'qr_codes/btc_' . $customer->id . '.png',
                    'xmr_qr' => 'qr_codes/xmr_' . $customer->id . '.png',
                    'sol_qr' => 'qr_codes/sol_' . $customer->id . '.png',
                    'is_default' => true,
                    'last_synced_at' => $faker->dateTimeBetween('-1 week', 'now'),
                ]
            );

            // Create some wallet transactions
        for ($i = 0; $i < $faker->numberBetween(2, 6); $i++) {
                // Generate an optional crypto-like address safely (avoid chaining on optional())
                $maybeAddress = $faker->boolean(60)
                    ? $faker->unique()->regexify('[a-zA-Z0-9]{34}')
                    : null;

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'customer_id' => $customer->id,
                    'type' => $faker->randomElement(['deposit', 'withdrawal', 'payment', 'refund']),
                    'amount' => $faker->randomFloat(2, 5, 200),
                    'status' => $faker->randomElement(['pending', 'completed', 'failed']),
            'reference' => 'txn_' . $wallet->id . '_' . $i . '_' . $faker->unique()->numerify('########'),
            'address' => $maybeAddress,
                    'payment_id' => 'txn_' . $faker->uuid(),
                    'description' => $faker->sentence(),
                    'metadata' => json_encode([
                        'payment_processor' => $faker->randomElement(['stripe', 'nowpayments']),
                        'exchange_rate' => $faker->randomFloat(4, 0.8, 1.2),
                        'fee_amount' => $faker->randomFloat(2, 0.5, 10),
                        'currency' => 'USD',
                        'payment_method' => $faker->randomElement(['stripe', 'crypto_btc', 'crypto_eth', 'crypto_usdt']),
                    ]),
                    'qr_code_path' => null,
                    'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
                    'updated_at' => $faker->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        }

        $this->command->info('Wallets and transactions seeded successfully!');
    }
}
