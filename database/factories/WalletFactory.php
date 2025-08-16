<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Wallet model to support tests.
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'balance' => $this->faker->randomFloat(2, 0, 500),
            'currency' => 'USD',
            'btc_address' => null,
            'xmr_address' => null,
            'sol_address' => null,
            'btc_qr' => null,
            'xmr_qr' => null,
            'sol_qr' => null,
            'last_synced_at' => null,
            'is_default' => true,
        ];
    }

    public function withBalance(float $amount): self
    {
        return $this->state(fn () => ['balance' => $amount]);
    }
}
