<?php

namespace Database\Seeders;

use App\Models\ServerInbound;
use App\Models\Server;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ServerInboundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $servers = Server::all();

        if ($servers->isEmpty()) {
            $this->command->warn('No servers found. Please run ServerSeeder first.');
            return;
        }

        $protocols = ['vless', 'vmess', 'trojan', 'shadowsocks', 'socks'];
        $networks = ['tcp', 'ws', 'grpc', 'http'];

        foreach ($servers as $server) {
            // Create 2-4 inbounds per server
            $inboundCount = $faker->numberBetween(2, 4);

            for ($i = 0; $i < $inboundCount; $i++) {
                $protocol = $faker->randomElement($protocols);
                $network = $faker->randomElement($networks);
                $port = $faker->numberBetween(10000, 65535);

                ServerInbound::create([
                    'server_id' => $server->id,
                    'remote_id' => $faker->numberBetween(1, 100),
                    'up' => $faker->numberBetween(0, 1000000000), // bytes
                    'down' => $faker->numberBetween(0, 1000000000), // bytes
                    'total' => $faker->numberBetween(1000000000, 10000000000), // bytes
                    'remote_up' => $faker->numberBetween(0, 1000000000),
                    'remote_down' => $faker->numberBetween(0, 1000000000),
                    'remote_total' => $faker->numberBetween(1000000000, 10000000000),
                    'remark' => $protocol . '_' . $port . '_' . $faker->word(),
                    'enable' => $faker->boolean(90), // 90% enabled
                    'expiry_time' => $faker->optional()->unixTime('+1 year') * 1000, // milliseconds
                    'listen' => '',
                    'port' => $port,
                    'protocol' => $protocol,
                    'settings' => json_encode([
                        'clients' => [],
                        'decryption' => 'none',
                        'fallbacks' => [],
                    ]),
                    'streamSettings' => json_encode([
                        'network' => $network,
                        'security' => $faker->randomElement(['none', 'tls', 'reality']),
                        'tlsSettings' => [
                            'serverName' => $faker->domainName(),
                            'certificates' => [],
                        ],
                    ]),
                    'remote_settings' => json_encode([
                        'clients' => [],
                        'decryption' => 'none',
                        'fallbacks' => [],
                    ]),
                    'remote_stream_settings' => json_encode([
                        'network' => $network,
                        'security' => $faker->randomElement(['none', 'tls', 'reality']),
                        'tlsSettings' => [
                            'serverName' => $faker->domainName(),
                            'certificates' => [],
                        ],
                    ]),
                    'tag' => $protocol . '_inbound_' . $port,
                    'sniffing' => json_encode([
                        'enabled' => true,
                        'destOverride' => ['http', 'tls'],
                    ]),
                    'remote_sniffing' => json_encode([
                        'enabled' => true,
                        'destOverride' => ['http', 'tls'],
                    ]),
                    'clientStats' => json_encode([
                        'total_clients' => $faker->numberBetween(0, 50),
                        'active_clients' => $faker->numberBetween(0, 20),
                        'inactive_clients' => $faker->numberBetween(0, 10),
                    ]),
                    'allocate' => json_encode([
                        'strategy' => 'always',
                        'refresh' => 5,
                        'concurrency' => 3,
                    ]),
                    'remote_allocate' => json_encode([
                        'strategy' => 'always',
                        'refresh' => 5,
                        'concurrency' => 3,
                    ]),
                    'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
                    'updated_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);
            }
        }

        $this->command->info('Server inbounds seeded successfully!');
    }
}
