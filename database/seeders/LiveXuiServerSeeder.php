<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\Server;
use App\Models\ServerInbound;
use App\Models\ServerPlan;

class LiveXuiServerSeeder extends Seeder
{
    /**
     * Seed the database with a real X-UI server, brand, category, inbounds, and plans.
     */
    public function run(): void
    {
        // Provided server details
        $host = 'amsterdam.1000proxy.me';
        $panelPort = 1111;
        $webPath = '/proxy';
        $username = '72A0Wvvv7o';
        $password = 'nyMT9YSIym';

        // Assumptions/defaults (use existing brand & categories)
        $countryName = 'Netherlands';
        $countryCode = 'NL';
        $scheme = 'http'; // default to http unless your panel is behind TLS with a valid cert

        DB::transaction(function () use ($countryName, $countryCode, $host, $panelPort, $webPath, $username, $password, $scheme) {
            // 1) Brand: use an existing active brand (prefer ProxyTitan if present)
            $brand = ServerBrand::where('slug', 'proxy-titan')->first()
                ?: ServerBrand::where('is_active', true)->first();

            if (! $brand) {
                throw new \RuntimeException('No active server brand found. Please run ServerBrandSeeder first.');
            }

            // 2) Category: use an existing active category (prefer Streaming if present)
            $category = ServerCategory::where('slug', 'streaming')->first()
                ?: ServerCategory::where('is_active', true)->first();

            if (! $category) {
                throw new \RuntimeException('No active server category found. Please run ServerCategorySeeder first.');
            }

            // 3) Server record
            $panelUrl = sprintf('%s://%s:%d%s', $scheme, $host, $panelPort, rtrim('/' . trim($webPath, '/'), '/'));

        $server = Server::updateOrCreate(
                [
                    'host' => $host,
                    'panel_port' => $panelPort,
                ],
                [
                    'name' => 'Amsterdam #1',
                    'server_brand_id' => $brand->id,
                    'server_category_id' => $category->id,
                    'username' => $username,
                    'password' => $password,
                    'web_base_path' => $webPath,
                    'panel_url' => $panelUrl,
                    'country' => $countryName,
                    'status' => 'up',
                    // Required column; set placeholder if DNS resolution not performed here
                    'ip' => '0.0.0.0',
                    'port' => 443,
                    'api_version' => '3x-ui',
                    'api_capabilities' => ['login','inbounds','clients','traffic'],
                    'auto_provisioning' => true,
                    'auto_sync_enabled' => true,
                    'sync_interval_minutes' => 5,
                ]
            );

            // 4) Default protocol inbounds
            $inboundsSpec = [
                'vless' => [
                    'port' => 2053,
                    'network' => 'ws',
                    'security' => 'tls',
                    'tag' => 'vless_ws_tls',
                    'is_default' => true,
                ],
                'vmess' => [
                    'port' => 2096,
                    'network' => 'ws',
                    'security' => 'tls',
                    'tag' => 'vmess_ws_tls',
                    'is_default' => false,
                ],
                'trojan' => [
                    'port' => 2443,
                    'network' => 'tcp',
                    'security' => 'tls',
                    'tag' => 'trojan_tls',
                    'is_default' => false,
                ],
                'shadowsocks' => [
                    'port' => 8388,
                    'network' => 'tcp',
                    'security' => 'none',
                    'tag' => 'ss_tcp',
                    'is_default' => false,
                ],
                'socks' => [
                    'port' => 1080,
                    'network' => 'tcp',
                    'security' => 'none',
                    'tag' => 'socks_tcp',
                    'is_default' => false,
                ],
            ];

            $inboundsByProtocol = [];
            foreach ($inboundsSpec as $protocol => $spec) {
                $inbound = ServerInbound::updateOrCreate(
                    [
                        'server_id' => $server->id,
                        'protocol' => $protocol,
                        'port' => $spec['port'],
                    ],
                    [
                        'tag' => $spec['tag'],
                        'listen' => '',
                        'enable' => true,
                        'remark' => strtoupper($protocol) . ' ' . $host,
                        'status' => 'active',
                        'provisioning_enabled' => true,
                        'is_default' => (bool) $spec['is_default'],
                        'capacity' => 500,
                        'current_clients' => 0,
                        'settings' => [
                            'clients' => [],
                            'decryption' => 'none',
                            'fallbacks' => [],
                        ],
                        'streamSettings' => [
                            'network' => $spec['network'],
                            'security' => $spec['security'],
                            'tlsSettings' => [
                                'serverName' => $host,
                            ],
                            'wsSettings' => $spec['network'] === 'ws' ? [
                                'path' => $webPath,
                                'headers' => ['Host' => $host],
                            ] : null,
                        ],
                        'sniffing' => [
                            'enabled' => true,
                            'destOverride' => ['http', 'tls'],
                        ],
                        'allocate' => [
                            'strategy' => 'always',
                            'refresh' => 5,
                            'concurrency' => 3,
                        ],
                    ]
                );

                $inboundsByProtocol[$protocol] = $inbound;
            }

            // Plans will be generated by ServerSeeder to avoid duplication here.
        });
    }
}
