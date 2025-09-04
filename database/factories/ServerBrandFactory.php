<?php

namespace Database\Factories;

use App\Models\ServerBrand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerBrand>
 */
class ServerBrandFactory extends Factory
{
    protected $model = ServerBrand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    $name = $this->faker->company . ' Proxy';

    // Robust unique slug: append a short random suffix for test isolation
    $baseSlug = Str::slug($name);
    $slug = $baseSlug . '-' . Str::random(6);

        return [
            'name' => $name,
            'slug' => $slug,
            'image' => 'server_brands/' . $slug . '.png',
            'desc' => $this->faker->paragraph(3),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the brand is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the brand is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true, // Featured brands should be active
        ]);
    }

    /**
     * Indicate that the brand is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the brand tier.
     */
    public function tier(string $tier): static
    {
        return $this->state(fn (array $attributes) => [
            // Tier functionality not available in current schema
        ]);
    }

    /**
     * Generate a premium tier brand.
     */
    public function premium(): static
    {
        return $this->active();
    }

    /**
     * Generate a standard tier brand.
     */
    public function standard(): static
    {
        return $this->active();
    }

    /**
     * Generate a budget tier brand.
     */
    public function budget(): static
    {
        return $this->active();
    }

    /**
     * Generate an enterprise tier brand.
     */
    public function enterprise(): static
    {
        return $this->active();
    }
}
