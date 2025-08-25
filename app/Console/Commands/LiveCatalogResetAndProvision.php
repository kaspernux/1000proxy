<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\{Server, ServerPlan, ServerInbound, Customer};
use App\Services\{XUIService, ClientProvisioningService};

class LiveCatalogResetAndProvision extends Command
{
    protected $signature = 'live:reset-and-provision
        {--server= : Server ID of the live X-UI}
        {--host= : Server host or panel host to resolve the Server record}
        {--customer= : Customer ID to use (optional; will create demo if omitted)}
        {--wallet=10004 : Wallet top-up amount for demo customer}
        {--protocols=vless,vmess : Comma list of protocols to prepare}
        {--vless-inbound-id=1 : Default inbound id to use for vless (remote)}
        {--vmess-inbound-id=3 : Default inbound id to use for vmess (remote)}
        {--dry-run : Do not mutate remote (login+sync only)}
        {--json : Output JSON summary}';

    protected $description = 'Purge plans, seed SEO plans per protocol (shared/dedicated), ensure inbounds exist (create if missing), and run a live wallet order to provision all plans. You can target the server by --server or --host.';

    public function handle(): int
    {
        // Resolve server by ID or host/panel URL
        $server = null;
        $serverId = (int) $this->option('server');
        $serverHost = trim((string) $this->option('host'));
        if ($serverId) {
            $server = Server::find($serverId);
        } elseif ($serverHost !== '') {
            $server = Server::query()
                ->where('host', $serverHost)
                ->orWhere('panel_url', 'like', "%{$serverHost}%")
                ->first();
        }
        if (!$server) {
            $this->error('Server not found. Provide --server ID or --host to locate it.');
            return 1;
        }

        $dry = (bool) $this->option('dry-run');
        $json = (bool) $this->option('json');

        // 1) Login and full inbound sync
        $xui = new XUIService($server);
        if (!$xui->testConnection()) { $this->error('Login failed to X-UI'); return 1; }
        $synced = $xui->syncAllInbounds();
        $this->line("Synced {$synced} inbounds");

        // Derive and persist basic remote info (location + timezone guess)
        try {
            $country = (string) ($server->country ?? '');
            $tz = $this->guessTimezone($country);
            $server->updatePerformanceMetrics([
                'geo' => [
                    'host' => $server->getPanelHost(),
                    'country' => $country,
                    'timezone' => $tz,
                ],
            ]);
            $this->line('Server geo/timezone set to: ' . ($tz ?: 'UTC'));
        } catch (\Throwable $e) {
            // non-fatal
        }

        // 2) Ensure base shared inbounds per protocol using requested remote IDs
        $protocols = array_filter(array_map('trim', explode(',', (string)$this->option('protocols'))));
        $remoteIdMap = [
            'vless' => (int) $this->option('vless-inbound-id'),
            'vmess' => (int) $this->option('vmess-inbound-id'),
        ];

        $remoteInbounds = collect($xui->listInbounds());
        $preferred = [];
        foreach ($protocols as $proto) {
            $wantId = $remoteIdMap[$proto] ?? null;
            $found = null;
            if ($wantId) {
                $found = $remoteInbounds->firstWhere('id', $wantId);
            }
            if (!$found) {
                // create minimal shared inbound for this protocol
                if ($dry) {
                    $this->warn("Dry-run: would create shared inbound for {$proto}");
                } else {
                    // pick a free port avoiding collisions
                    $ports = $remoteInbounds->pluck('port')->filter()->values()->all();
                    $port = 35000;
                    while (in_array($port, $ports, true)) { $port++; }
                    $payload = [
                        'up' => 0, 'down' => 0, 'total' => 0,
                        'remark' => strtoupper($proto) . ' SHARED AUTO',
                        'enable' => true,
                        'expiryTime' => 0,
                        'listen' => '',
                        'port' => $port,
                        'protocol' => $proto,
                        'settings' => json_encode(['clients' => [], 'decryption' => 'none', 'fallbacks' => []]),
                        'streamSettings' => json_encode(['network' => 'tcp', 'security' => $proto === 'vless' ? 'reality' : 'none', 'tcpSettings' => ['header' => ['type' => 'none']]]),
                        'sniffing' => json_encode(['enabled' => false,'destOverride'=>['http','tls','quic','fakedns'],'metadataOnly'=>false,'routeOnly'=>false]),
                        'allocate' => json_encode(['strategy' => 'always','refresh'=>5,'concurrency'=>3]),
                    ];
                    $created = $xui->createInbound($payload);
                    if (!($created['id'] ?? null)) { $this->error("Failed to create {$proto} inbound"); return 1; }
                    $xui->syncInbound($created);
                    $found = $created;
                    $this->info("Created {$proto} shared inbound on port {$created['port']}");
                }
            }
            if ($found) {
                $preferred[$proto] = ServerInbound::where('server_id', $server->id)->where('remote_id', $found['id'])->first();
            }
        }

        // If dry-run, stop before mutating local DB or remote
        if ($dry) {
            $this->info('Dry-run: skipping local DB mutations, plan seeding, wallet top-up, and provisioning.');
            return 0;
        }

        // 3) Purge existing plans
        $this->warn('Deleting all existing server plans...');
        DB::transaction(function(){
            DB::table('order_items')->delete();
            DB::table('server_clients')->delete();
            DB::table('server_plans')->delete();
        });

        // 4) Seed SEO plans per protocol (shared + dedicated)
        $plans = [];
        foreach ($protocols as $proto) {
            $shared = ServerPlan::create($this->planAttrs($server, $proto, 'shared', $preferred[$proto] ?? null));
            $dedic = ServerPlan::create($this->planAttrs($server, $proto, 'dedicated', $preferred[$proto] ?? null));
            $plans[] = $shared; $plans[] = $dedic;
        }
        $this->info('Seeded plans: ' . implode(', ', array_map(fn($p) => $p->slug, $plans)));

        // 5) Demo customer + wallet
        $customer = $this->option('customer') ? Customer::find($this->option('customer')) : null;
        if (!$customer) {
            $customer = Customer::factory()->create();
        }
        $walletTopup = (float) $this->option('wallet');
        if ($walletTopup > 0) { $customer->addToWallet($walletTopup, 'Go-live top-up'); }

        if ($dry) { $this->info('Dry-run: skipping order+provision'); return 0; }

        // 6) Buy all plans and provision
        $prov = app()->makeWith(ClientProvisioningService::class, ['xuiService' => new XUIService($server)]);
        $results = [];
        foreach ($plans as $plan) {
            // pay from wallet
            $cost = $plan->price;
            if ($customer->getWallet()->balance < $cost) { $this->error('Insufficient wallet for plan ' . $plan->slug); break; }
            $customer->payFromWallet($cost, 'Go-live purchase');

            $order = \App\Models\Order::create([
                'customer_id' => $customer->id,
                'grand_amount' => $cost,
                'currency' => 'USD',
                'payment_status' => 'paid',
                'order_status' => 'new',
            ]);
            $item = \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'server_plan_id' => $plan->id,
                'quantity' => 1,
                'total_amount' => $cost,
                'unit_amount' => $plan->price,
            ]);
            $res = $prov->provisionOrder($order->fresh('items.serverPlan'));
            $results[$plan->slug] = $res;
        }

        if ($json) { $this->line(json_encode(['results' => $results], JSON_PRETTY_PRINT)); }
        else { $this->info('Provisioning complete for ' . count($plans) . ' plans'); }
        return 0;
    }

    private function guessTimezone(string $country): ?string
    {
        $map = [
            'NL' => 'Europe/Amsterdam', 'NETHERLANDS' => 'Europe/Amsterdam',
            'US' => 'America/New_York', 'UNITED STATES' => 'America/New_York',
            'GB' => 'Europe/London', 'UK' => 'Europe/London', 'UNITED KINGDOM' => 'Europe/London',
            'DE' => 'Europe/Berlin', 'GERMANY' => 'Europe/Berlin',
            'FR' => 'Europe/Paris', 'FRANCE' => 'Europe/Paris',
            'AE' => 'Asia/Dubai', 'UNITED ARAB EMIRATES' => 'Asia/Dubai',
            'SG' => 'Asia/Singapore', 'SINGAPORE' => 'Asia/Singapore',
        ];
        $key = strtoupper(trim($country));
        return $map[$key] ?? (function(){
            try { return \date_default_timezone_get(); } catch (\Throwable $e) { return 'UTC'; }
        })();
    }

    private function planAttrs(Server $server, string $proto, string $mode, ?ServerInbound $preferred): array
    {
        $city = strtoupper(Str::slug($server->country ?? 'Global', ' '));
        $modeLabel = $mode === 'dedicated' ? 'Dedicated' : 'Shared';
        $name = "{$city} Datacenter {$proto} Proxy {$modeLabel} " . Str::upper(Str::random(3));
        $slug = strtolower($proto) . "-proxy-" . ($mode === 'dedicated' ? 'dedic' : 'shared') . '-' . Str::lower(Str::random(3));
        return [
            'server_id' => $server->id,
            'name' => $name,
            'slug' => $slug,
            'description' => "High-performance {$proto} {$modeLabel} plan in {$city} with instant provisioning.",
            'price' => $mode === 'dedicated' ? 12.00 : 8.00,
            'type' => $mode === 'dedicated' ? 'single' : 'multiple',
            'days' => 30,
            'volume' => 50,
            'is_active' => true,
            'in_stock' => true,
            'on_sale' => true,
            'server_category_id' => $server->server_category_id,
            'server_brand_id' => $server->server_brand_id,
            'max_clients' => $mode === 'dedicated' ? 1 : 200,
            'protocol' => $proto,
            'preferred_inbound_id' => $preferred?->id,
        ];
    }
}
