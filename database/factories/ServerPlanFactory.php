<?php

namespace Database\Factories;

use App\Models\ServerPlan;
use App\Models\Server;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServerPlan>
 */
class ServerPlanFactory extends Factory
{
    protected $model = ServerPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $planTypes = ['Standard', 'Premium', 'Elite', 'Ultimate', 'Professional'];
        $protocols = ['vless', 'vmess', 'trojan', 'shadowsocks', 'mixed'];
        $types = ['single', 'multiple', 'dedicated', 'branded'];
        $countries = ['US', 'UK', 'DE', 'FR', 'JP', 'CA', 'AU', 'NL', 'SE', 'SG'];

        $name = $this->faker->randomElement($planTypes) . ' ' . $this->faker->randomElement(['Proxy', 'VPN', 'Server']);

        return [
            'server_id' => Server::factory(),
            'server_brand_id' => ServerBrand::factory(),
            'server_category_id' => ServerCategory::factory(),
            'country_code' => $this->faker->randomElement($countries),
            'region' => $this->faker->state(),
            'protocol' => $this->faker->randomElement($protocols),
            'bandwidth_mbps' => $this->faker->numberBetween(100, 1000),
            'supports_ipv6' => $this->faker->boolean(30),
            'popularity_score' => $this->faker->numberBetween(0, 100),
            'server_status' => $this->faker->randomElement(['online', 'offline', 'maintenance']),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'product_image' => 'server_plans/' . \Illuminate\Support\Str::slug($name) . '.jpg',
            'description' => $this->faker->paragraph(2),
            'capacity' => $this->faker->numberBetween(50, 1000),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'type' => $this->faker->randomElement($types),
            'days' => $this->faker->randomElement([30, 90, 365]),
            'volume' => $this->faker->numberBetween(10, 1000),
            'is_active' => $this->faker->boolean(80),
            'is_featured' => $this->faker->boolean(20),
            'is_popular' => $this->faker->boolean(15),
            'in_stock' => $this->faker->boolean(90),
            'on_sale' => $this->faker->boolean(15),
        ];
    }

    /**
     * Indicate that the plan is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the plan is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the plan is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => true,
        ]);
    }

    /**
     * Indicate that the plan is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => false,
        ]);
    }

    /**
     * Indicate that the plan is on sale.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'on_sale' => true,
        ]);
    }

    /**
     * Set specific price.
     */
    public function price(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    /**
     * Set specific capacity.
     */
    public function capacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }

    /**
     * Generate a premium plan.
     */
    public function premium(): static
    {
        return $this->featured()->price($this->faker->randomFloat(2, 50, 200));
    }

    /**
     * Generate a basic plan.
     */
    public function basic(): static
    {
        return $this->price($this->faker->randomFloat(2, 5, 25));
    }
}
