<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Wallet',
                'slug' => Str::slug('Wallet'),
                'type' => 'wallet',
                'notes' => 'Payments made directly from customer wallet balance.',
                'image' => 'payment_methods/wallet.svg',
                'is_active' => true,
            ],
            [
                'name' => 'Ethereum',
                'slug' => Str::slug('Ethereum'),
                'type' => 'ethereum',
                'notes' => 'Crypto payments with ETH network.',
                'image' => 'payment_methods/ethereum.svg',
                'is_active' => true,
            ],
            [
                'name' => 'NowPayments',
                'slug' => Str::slug('NowPayments'),
                'type' => 'nowpayments',
                'notes' => 'Crypto payment gateway supporting multiple cryptocurrencies.',
                'image' => 'payment_methods/nowpayments.svg',
                'is_active' => true,
            ],
            [
                'name' => 'Stripe',
                'slug' => Str::slug('Stripe'),
                'type' => 'stripe',
                'notes' => 'Payment gateway for credit card and other online payments.',
                'image' => 'payment_methods/stripe.svg',
                'is_active' => false,
            ],
            [
                'name' => 'PayPal',
                'slug' => Str::slug('PayPal'),
                'type' => 'paypal',
                'notes' => 'Online payment system supporting payments and money transfers.',
                'image' => 'payment_methods/paypal.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Bitcoin',
                'slug' => Str::slug('Bitcoin'),
                'type' => 'bitcoin',
                'notes' => 'Popular cryptocurrency for digital payments.',
                'image' => 'payment_methods/bitcoin.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Monero',
                'slug' => Str::slug('Monero'),
                'type' => 'monero',
                'notes' => 'Privacy-focused cryptocurrency.',
                'image' => 'payment_methods/monero.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Giftcard',
                'slug' => Str::slug('Giftcard'),
                'type' => 'giftcard',
                'notes' => 'Payments made using various gift cards.',
                'image' => 'payment_methods/gift-card.svg',
                'is_active' => false,
            ],
            [
                'name' => 'MIR',
                'slug' => Str::slug('MIR'),
                'type' => 'mir',
                'notes' => 'Russian national payment system.',
                'image' => 'payment_methods/mir.svg',
                'is_active' => false,
            ],
            [
                'name' => 'VISA/MSC',
                'slug' => Str::slug('VISA/MSC'),
                'type' => 'visa',
                'notes' => 'Payments made using Visa and MasterCard credit/debit cards.',
                'image' => 'payment_methods/visa.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Others',
                'slug' => Str::slug('Others'),
                'type' => 'others',
                'notes' => 'Other payment methods not specifically listed.',
                'image' => 'payment_methods/web-money.svg',
                'is_active' => false,
            ],
        ];

        // Use updateOrCreate to avoid duplicate entry errors
        foreach ($paymentMethods as $method) {
            \App\Models\PaymentMethod::updateOrCreate(
                ['slug' => $method['slug']], // Check by slug
                $method // Update with all data
            );
        }
    }
}
