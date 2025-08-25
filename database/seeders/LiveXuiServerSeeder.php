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
use App\Services\XUIService;

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

            // 2) Category: prefer Datacenter Proxies, otherwise any active
            $category = ServerCategory::where('slug', 'datacenter-proxies')->first()
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

            // 4) Sync protocol inbounds with remote 3X-UI (avoid duplicate ports; create if missing)
            $inboundsSpec = [
                'vless' => [ 'port' => 2053, 'network' => 'ws',  'security' => 'tls', 'tag' => 'vless_ws_tls',  'is_default' => true  ],
                'vmess' => [ 'port' => 2096, 'network' => 'ws',  'security' => 'tls', 'tag' => 'vmess_ws_tls',  'is_default' => false ],
                'trojan' => [ 'port' => 2443, 'network' => 'tcp', 'security' => 'tls', 'tag' => 'trojan_tls',    'is_default' => false ],
                'shadowsocks' => [ 'port' => 8388, 'network' => 'tcp', 'security' => 'none', 'tag' => 'ss_tcp',  'is_default' => false ],
                'socks' => [ 'port' => 1080, 'network' => 'tcp', 'security' => 'none', 'tag' => 'socks_tcp', 'is_default' => false ],
            ];

            $xui = new XUIService($server);
            // Try login; if login fails, we'll still seed locals to allow later sync
            $loggedIn = $xui->login();
            $remoteList = $loggedIn ? $xui->listInbounds() : [];
            $remoteByProtocol = collect($remoteList)->groupBy(function ($r) { return strtolower($r['protocol'] ?? ''); });
            $remotePorts = collect($remoteList)->pluck('port')->filter()->values()->all();

            $inboundsByProtocol = [];

            foreach ($inboundsSpec as $protocol => $spec) {
                $remoteInbound = optional($remoteByProtocol->get($protocol))->first();

                // If not found by protocol, try matching by preferred port
                if (!$remoteInbound) {
                    $remoteInbound = collect($remoteList)->first(function ($r) use ($protocol, $spec) {
                        return (strtolower($r['protocol'] ?? '') === $protocol) && ((int)($r['port'] ?? 0) === (int)$spec['port']);
                    });
                }

                // Create remotely if missing and we are logged in
                if (!$remoteInbound && $loggedIn) {
                    // Find a free port (start with desired; increment until free)
                    $candidate = (int) $spec['port'];
                    $tries = 0;
                    while (in_array($candidate, $remotePorts, true) && $tries < 200) {
                        $candidate++;
                        $tries++;
                    }

                    $settings = [ 'clients' => [], 'decryption' => 'none', 'fallbacks' => [] ];
                    $stream = [ 'network' => $spec['network'], 'security' => $spec['security'] ];
                    if ($spec['network'] === 'ws') {
                        $stream['wsSettings'] = [ 'path' => $webPath, 'headers' => ['Host' => $host] ];
                    }
                    if ($spec['security'] === 'tls') {
                        $stream['tlsSettings'] = [ 'serverName' => $host ];
                    }
                    $sniffing = [ 'enabled' => true, 'destOverride' => ['http', 'tls'] ];
                    $allocate = [ 'strategy' => 'always', 'refresh' => 5, 'concurrency' => 3 ];

                    $payload = [
                        'up' => 0,
                        'down' => 0,
                        'total' => 0,
                        'remark' => strtoupper($protocol) . ' ' . $host,
                        'enable' => true,
                        'expiryTime' => 0,
                        'listen' => '',
                        'port' => $candidate,
                        'protocol' => $protocol,
                        'settings' => json_encode($settings, JSON_UNESCAPED_SLASHES),
                        'streamSettings' => json_encode($stream, JSON_UNESCAPED_SLASHES),
                        'tag' => $spec['tag'],
                        'sniffing' => json_encode($sniffing, JSON_UNESCAPED_SLASHES),
                        'allocate' => json_encode($allocate, JSON_UNESCAPED_SLASHES),
                    ];

                    $created = $xui->createInbound($payload);
                    if (!empty($created)) {
                        $remoteInbound = $created; // use remote parameters returned
                        $remotePorts[] = (int) ($remoteInbound['port'] ?? $candidate);
                    }
                }

                // Upsert local from remote (or seed local fallback if remote not reachable)
                if ($remoteInbound) {
                    // Ensure array shape
                    $remote = $remoteInbound;
                    $local = ServerInbound::updateOrCreate(
                        [ 'server_id' => $server->id, 'port' => (int)($remote['port'] ?? $spec['port']) ],
                        [ 'protocol' => strtolower($remote['protocol'] ?? $protocol) ]
                    );
                    $local->updateFromXuiApiData($remote);
                    $local->is_default = (bool) $spec['is_default'];
                    $local->provisioning_enabled = true;
                    $local->status = 'active';
                    $local->save();

                    $inboundsByProtocol[$protocol] = $local;
                } else {
                    // Remote unavailable: seed a local placeholder (no clients) to be synced later
                    $local = ServerInbound::updateOrCreate(
                        [ 'server_id' => $server->id, 'port' => (int)$spec['port'] ],
                        [
                            'protocol' => $protocol,
                            'tag' => $spec['tag'],
                            'listen' => '',
                            'enable' => true,
                            'remark' => strtoupper($protocol) . ' ' . $host,
                            'status' => 'active',
                            'provisioning_enabled' => true,
                            'is_default' => (bool) $spec['is_default'],
                            'capacity' => 500,
                            'current_clients' => 0,
                            'settings' => [ 'clients' => [], 'decryption' => 'none', 'fallbacks' => [] ],
                            'streamSettings' => [ 'network' => $spec['network'], 'security' => $spec['security'] ],
                            'sniffing' => [ 'enabled' => true, 'destOverride' => ['http','tls'] ],
                            'allocate' => [ 'strategy' => 'always', 'refresh' => 5, 'concurrency' => 3 ],
                        ]
                    );
                    $inboundsByProtocol[$protocol] = $local;
                }
            }

            // Plans will be seeded separately to avoid duplication here.
        });
    }
}
