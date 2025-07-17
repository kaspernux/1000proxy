<?php

namespace Database\Seeders;

use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ServerClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $serverInbounds = ServerInbound::with('server')->get();
        $completedOrders = Order::where('order_status', 'completed')->with('customer')->get();

        if ($serverInbounds->isEmpty()) {
            $this->command->warn('No server inbounds found. Please run ServerInboundSeeder first.');
            return;
        }

        if ($completedOrders->isEmpty()) {
            $this->command->warn('No completed orders found. Some clients will be created without orders.');
        }

        foreach ($serverInbounds as $inbound) {
            // Create 5-20 clients per inbound
            $clientCount = $faker->numberBetween(5, 20);

            for ($i = 0; $i < $clientCount; $i++) {
                $order = $completedOrders->isNotEmpty() ? $completedOrders->random() : null;
                $customer = $order ? $order->customer : Customer::inRandomOrder()->first();

                if (!$customer) continue;

                $uuid = Str::uuid()->toString();
                $email = $customer->email . '+client' . $i . '_' . $inbound->id . '@proxy.local';
                $expiryTime = $faker->optional(70)->unixTime('+1 year'); // 70% have expiry
                $totalGB = $faker->numberBetween(10, 500);
                $usedGB = $faker->numberBetween(0, min($totalGB * 0.8, $totalGB));

                ServerClient::create([
                    'id' => $uuid,
                    'server_inbound_id' => $inbound->id,
                    'order_id' => $order?->id,
                    'customer_id' => $customer->id,
                    'email' => $email,
                    'password' => null,
                    'enable' => $faker->boolean(85), // 85% enabled
                    'remote_up' => $usedGB * 1024 * 1024 * 1024 * 0.3, // 30% upload
                    'remote_down' => $usedGB * 1024 * 1024 * 1024 * 0.7, // 70% download
                    'remote_total' => $usedGB * 1024 * 1024 * 1024,
                    'expiry_time' => $expiryTime ? $expiryTime * 1000 : 0, // Convert to milliseconds
                    'total_gb_bytes' => $totalGB * 1024 * 1024 * 1024, // Convert to bytes
                    'limit_ip' => $faker->numberBetween(1, 10),
                    'tg_id' => $faker->optional(30)->numerify('##########') ?? '',
                    'sub_id' => Str::random(16),
                    'reset' => 0,
                    'flow' => $faker->randomElement(['', 'xtls-rprx-vision', 'xtls-rprx-direct']),
                    'remote_client_id' => $faker->numberBetween(1, 1000),
                    'remote_inbound_id' => $faker->numberBetween(1, 100),
                    'api_sync_status' => $faker->randomElement(['pending', 'success', 'error']),
                    'is_online' => $faker->boolean(60),
                    'plan_id' => null,
                    'status' => $faker->randomElement(['active', 'suspended', 'expired', 'depleted']),
                    'provisioned_at' => $faker->optional()->dateTimeBetween('-3 months', 'now'),
                    'activated_at' => $faker->optional()->dateTimeBetween('-3 months', 'now'),
                    'last_connection_at' => $faker->optional()->dateTimeBetween('-1 week', 'now'),
                    'traffic_limit_mb' => $totalGB * 1024,
                    'traffic_used_mb' => $usedGB * 1024,
                    'traffic_percentage_used' => $totalGB > 0 ? round(($usedGB / $totalGB) * 100, 2) : 0,
                    'connection_count' => $faker->numberBetween(0, 100),
                    'client_config' => json_encode([
                        'protocol' => $inbound->protocol,
                        'port' => $inbound->port,
                        'encryption' => $faker->randomElement(['none', 'auto', 'aes-128-gcm']),
                        'network' => $faker->randomElement(['tcp', 'ws', 'grpc']),
                        'security' => $faker->randomElement(['none', 'tls', 'reality']),
                        'sni' => $faker->optional()->domainName(),
                        'path' => '/' . $faker->word(),
                        'host' => $faker->optional()->domainName(),
                    ]),
                    'client_link' => $this->generateConnectionLink($inbound, $uuid, $email),
                    'qr_code_client' => null, // Will be generated when needed
                    'auto_renew' => $faker->boolean(30),
                    'retry_count' => 0,
                    'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
                    'updated_at' => $faker->dateTimeBetween('-1 week', 'now'),
                ]);
            }
        }

        $this->command->info('Server clients seeded successfully!');
    }

    private function generateConnectionLink($inbound, $uuid, $email): string
    {
        $server = $inbound->server;
        $protocol = $inbound->protocol;

        switch ($protocol) {
            case 'vless':
                return "vless://{$uuid}@{$server->ip}:{$inbound->port}?type=tcp&security=none#{$email}";

            case 'vmess':
                $config = [
                    'v' => '2',
                    'ps' => $email,
                    'add' => $server->ip,
                    'port' => $inbound->port,
                    'id' => $uuid,
                    'aid' => '0',
                    'net' => 'tcp',
                    'type' => 'none',
                    'host' => '',
                    'path' => '',
                    'tls' => '',
                ];
                return 'vmess://' . base64_encode(json_encode($config));

            case 'trojan':
                return "trojan://{$uuid}@{$server->ip}:{$inbound->port}?security=tls#{$email}";

            case 'shadowsocks':
                $userInfo = base64_encode("aes-256-gcm:{$uuid}");
                return "ss://{$userInfo}@{$server->ip}:{$inbound->port}#{$email}";

            default:
                return "unknown://{$uuid}@{$server->ip}:{$inbound->port}#{$email}";
        }
    }
}
