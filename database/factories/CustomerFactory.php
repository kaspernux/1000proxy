<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => $this->faker->boolean,
            'image' => $this->faker->imageUrl(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'tgId' => $this->faker->randomNumber(9),
            'refcode' => Str::random(10),
            'wallet' => $this->faker->randomFloat(2, 0, 1000),
            'date' => $this->faker->date(),
            'phone' => $this->faker->numerify('##########'), // Generate a 10-digit number without formatting characters
            'refered_by' => null,
            'step' => $this->faker->randomDigit(),
            'freetrial' => $this->faker->boolean,
            'first_start' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'temp' => $this->faker->randomNumber(2),
            'is_agent' => $this->faker->boolean,
            'discount_percent' => $this->faker->randomFloat(2, 0, 100),
            'agent_date' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'spam_info' => $this->faker->text(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}