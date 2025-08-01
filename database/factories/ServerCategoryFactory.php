<?php

namespace Database\Factories;

use App\Models\ServerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerCategory>
 */
class ServerCategoryFactory extends Factory
{
    protected $model = ServerCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Gaming',
            'Streaming',
            'General',
            'Enterprise',
            'Privacy',
            'Social Media',
            'E-commerce',
            'Research',
            'Security',
            'Development'
        ];

        $name = $this->faker->randomElement($categories) . ' ' . $this->faker->randomElement(['Proxy', 'VPN', 'Server']);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'image' => 'server_categories/' . \Illuminate\Support\Str::slug($name) . '.png',
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Generate a gaming category.
     */
    public function gaming(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Gaming Proxy',
            'slug' => 'gaming-proxy',
        ]);
    }

    /**
     * Generate a streaming category.
     */
    public function streaming(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Streaming VPN',
            'slug' => 'streaming-vpn',
        ]);
    }

    /**
     * Generate a general category.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'General Purpose',
            'slug' => 'general-purpose',
        ]);
    }
}
