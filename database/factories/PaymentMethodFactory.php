<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for PaymentMethod model to support tests.
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Wallet','Credit Card','Crypto','PayPal']);
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'type' => $this->faker->randomElement(['wallet','card','crypto','gateway']),
            'notes' => $this->faker->sentence(),
            'is_active' => true,
            'gateway' => $this->faker->randomElement(['wallet','stripe','nowpayments']),
            'reference' => Str::uuid()->toString(),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
