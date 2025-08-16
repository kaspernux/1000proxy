<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Models\ServerPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\ServerClient>
 */
class ServerClientFactory extends Factory
{
    protected $model = ServerClient::class;

    public function definition(): array
    {
        // Ensure related records
        $server = Server::factory()->create();
        $inbound = ServerInbound::factory()->create(['server_id' => $server->id]);
        $plan = ServerPlan::factory()->create(['server_id' => $server->id]);

        $uuid = (string) Str::uuid();
        $subId = Str::random(10);

        return [
            'id' => $uuid,
            'uuid' => $uuid,
            'sub_id' => $subId,
            'server_inbound_id' => $inbound->id,
            'plan_id' => $plan->id,
            'order_id' => null,
            'customer_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $uuid,
            'flow' => 'xtls-rprx-vision',
            'limit_ip' => 0,
            'total_gb_bytes' => 50 * 1024 * 1024 * 1024, // 50GB
            'expiry_time' => now()->addMonth()->timestamp * 1000,
            'enable' => true,
            'reset' => 0,
            'remote_client_id' => null,
            'remote_inbound_id' => $inbound->remote_inbound_id ?? null,
            'remote_up' => 0,
            'remote_down' => 0,
            'remote_total' => 0,
            'remote_client_config' => null,
            'connection_ips' => [],
            'is_online' => false,
            'client_link' => 'vless://'.$uuid.'@'.$server->host.':'.$inbound->port.'#Client',
            'remote_sub_link' => 'http://'.$server->host.':'.$server->getSubscriptionPort().'/sub_proxy/'.$subId,
            'remote_json_link' => 'http://'.$server->host.':'.$server->getSubscriptionPort().'/json_proxy/'.$subId,
            'status' => 'active',
        ];
    }
}
