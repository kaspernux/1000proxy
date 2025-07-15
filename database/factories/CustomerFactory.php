<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'is_active' => true,
            'image' => $this->faker->imageUrl(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'telegram_chat_id' => $this->faker->randomNumber(9),
            'refcode' => Str::random(10),
            'date' => $this->faker->date(),
            'phone' => $this->faker->numerify('##########'),
            'refered_by' => null,
            'step' => 'none',
            'freetrial' => false,
            'first_start' => now(),
            'temp' => null,
            'is_agent' => false,
            'discount_percent' => 0,
            'agent_date' => now(),
            'spam_info' => null,
        ];
    }

    private function generateBtcAddress(): string
    {
        // Real BTC addresses usually start with 1, 3, or bc1
        $prefix = $this->faker->randomElement(['1', '3', 'bc1q']);
        if ($prefix === 'bc1q') {
            return 'bc1q' . Str::random(39);
        }
        return $prefix . Str::random(33);
    }

    private function generateXmrAddress(): string
    {
        // Real Monero addresses are 95 characters long and start with '4'
        return '4' . Str::random(94);
    }

    private function generateSolAddress(): string
    {
        // Solana addresses are base58 and about 44 characters long
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        return collect(range(1, 44))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
