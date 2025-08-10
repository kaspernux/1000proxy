<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ServerInbound;
use App\Models\Server;

class ServerInboundFactory extends Factory
{
    protected $model = ServerInbound::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'port' => $this->faker->numberBetween(10000, 40000),
            'protocol' => $this->faker->randomElement(['vless','vmess']),
            'remark' => $this->faker->sentence(2),
            'enable' => true,
            'expiry_time' => 0,
            'settings' => ['clients' => []],
            'streamSettings' => ['network' => 'tcp'],
            'sniffing' => [],
            'allocate' => [],
            'up' => 0,
            'down' => 0,
            'total' => 0,
            'provisioning_enabled' => true,
            'is_default' => false,
            'capacity' => 100,
            'current_clients' => 0,
            'status' => 'active',
            'remote_id' => app()->environment('testing') ? $this->faker->numberBetween(1000,9999) : null,
        ];
    }
}
