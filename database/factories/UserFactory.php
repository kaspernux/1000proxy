<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => fake()->randomElement(['admin', 'support_manager', 'sales_support']),
            'is_active' => true,
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'telegram_chat_id' => fake()->optional()->numberBetween(100000000, 999999999),
            'telegram_username' => fake()->optional()->userName(),
            'telegram_first_name' => fake()->optional()->firstName(),
            'telegram_last_name' => fake()->optional()->lastName(),
            'remember_token' => Str::random(10),
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

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Create a support manager user.
     */
    public function supportManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'support_manager',
            'is_active' => true,
        ]);
    }

    /**
     * Create a sales support user.
     */
    public function salesSupport(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'sales_support',
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a user with Telegram linked.
     */
    public function withTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_chat_id' => fake()->numberBetween(100000000, 999999999),
            'telegram_username' => fake()->userName(),
            'telegram_first_name' => fake()->firstName(),
            'telegram_last_name' => fake()->lastName(),
        ]);
    }
}
