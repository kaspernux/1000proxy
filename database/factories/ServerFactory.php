<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    protected $model = Server::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = ['US', 'UK', 'DE', 'FR', 'JP', 'CA', 'AU', 'NL', 'SE', 'SG'];
        $flags = ['🇺🇸', '🇬🇧', '🇩🇪', '🇫🇷', '🇯🇵', '🇨🇦', '🇦🇺', '🇳🇱', '🇸🇪', '🇸🇬'];
        $country = $this->faker->randomElement($countries);
        $countryIndex = array_search($country, $countries);

        return [
            'name' => $this->faker->city() . ' Server ' . $this->faker->numberBetween(1, 100),
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'server_category_id' => ServerCategory::factory(),
            'server_brand_id' => ServerBrand::factory(),
            'country' => $country,
            'flag' => $flags[$countryIndex] ?? '🌐',
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['up', 'down', 'maintenance']),
            'host' => $this->faker->ipv4(),
            'panel_port' => $this->faker->numberBetween(8000, 9999),
            'web_base_path' => $this->faker->optional(0.3)->slug(),
            'panel_url' => 'http://' . $this->faker->ipv4() . ':' . $this->faker->numberBetween(8000, 9999),
            'ip' => $this->faker->ipv4(),
            'port' => $this->faker->numberBetween(443, 65535),
            'sni' => $this->faker->optional(0.7)->domainName(),
            'header_type' => $this->faker->randomElement(['none', 'http']),
            'request_header' => $this->faker->optional(0.3)->word(),
            'response_header' => $this->faker->optional(0.3)->word(),
            'security' => $this->faker->randomElement(['reality', 'tls', 'none']),
            'tlsSettings' => [
                'allowInsecure' => false,
                'serverName' => $this->faker->domainName(),
            ],
            'type' => $this->faker->randomElement(['proxy', 'vpn']),
            'port_type' => $this->faker->randomElement(['tcp', 'udp']),
            'reality' => [
                'enabled' => $this->faker->boolean(),
                'dest' => $this->faker->domainName() . ':443',
            ],
            'xui_config' => [
                'api_enabled' => true,
                'sync_interval' => 300,
                'auto_backup' => true,
            ],
            'connection_settings' => [
                'timeout' => 30,
                'max_retries' => 3,
                'verify_ssl' => true,
            ],
            'last_connected_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'health_status' => $this->faker->randomElement(['healthy', 'warning', 'critical']),
            'performance_metrics' => [
                'cpu_usage' => $this->faker->numberBetween(10, 90) . '%',
                'memory_usage' => $this->faker->numberBetween(20, 80) . '%',
                'uptime' => $this->faker->numberBetween(1, 365) . ' days',
            ],
            'total_clients' => $this->faker->numberBetween(0, 1000),
            'active_clients' => $this->faker->numberBetween(0, 500),
            'max_capacity' => $this->faker->numberBetween(100, 2000),
            'session_cookie' => $this->faker->optional(0.7)->sha256(),
            'last_login_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'api_url' => 'http://' . $this->faker->ipv4() . ':' . $this->faker->numberBetween(8000, 9999) . '/panel/api',
            'subscription_port' => $this->faker->numberBetween(2080, 2090),
            'subscription_path' => '/sub',
            'total_inbounds' => $this->faker->numberBetween(1, 10),
            'active_inbounds' => $this->faker->numberBetween(1, 5),
            'is_active' => $this->faker->boolean(85),
            'auto_provisioning' => $this->faker->boolean(70),
            'auto_sync_enabled' => $this->faker->boolean(60),
            'auto_cleanup_depleted' => $this->faker->boolean(50),
            'backup_notifications_enabled' => $this->faker->boolean(40),
        ];
    }

    /**
     * Indicate that the server is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'status' => 'up',
        ]);
    }

    /**
     * Indicate that the server is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => 'down',
        ]);
    }

    /**
     * Indicate that the server is healthy.
     */
    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'health_status' => 'healthy',
            'status' => 'up',
        ]);
    }

    /**
     * Set server location.
     */
    public function location(string $country, string $flag = null): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => $country,
            'flag' => $flag ?? '🌐',
        ]);
    }

    /**
     * Generate a US server.
     */
    public function us(): static
    {
        return $this->location('US', '🇺🇸');
    }

    /**
     * Generate a UK server.
     */
    public function uk(): static
    {
        return $this->location('UK', '🇬🇧');
    }

    /**
     * Generate a German server.
     */
    public function de(): static
    {
        return $this->location('DE', '🇩🇪');
    }
}
