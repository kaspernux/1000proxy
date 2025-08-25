<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\{Server, ServerPlan, Order, OrderItem, Customer};
use App\Services\{XUIService, ClientProvisioningService};

class SimulateLiveOrders extends Command
{
    protected $signature = 'orders:simulate-live
        {--server= : Existing server ID}
        {--panel= : Panel base URL (updates server if provided)}
        {--username= : Panel username}
        {--password= : Panel password}
        {--wallet=200 : Wallet top-up amount (numeric)}
        {--shared-quantity=2 : Number of shared clients to provision}
        {--dedicated-quantity=1 : Number of dedicated (single) orders to create}
        {--shared-plan= : Reuse existing shared plan ID}
        {--dedicated-plan= : Reuse existing dedicated plan ID}
        {--shared-plan-slug= : Use existing shared plan by slug (if id not provided)}
        {--dedicated-plan-slug= : Use existing dedicated plan by slug (if id not provided)}
        {--dedicated-port-min=20000 : Min port for dedicated inbound allocation}
        {--dedicated-port-max=60000 : Max port for dedicated inbound allocation}
        {--insecure : Disable TLS verification}
        {--diagnostics : Extra output}
        {--skip-shared : Skip shared order}
        {--skip-dedicated : Skip dedicated order}
    {--json : Output machine-readable JSON summary}
    {--dry-run : Do not mutate remote (still logs in & syncs)}';

    protected $description = 'Simulate real shared & dedicated orders end-to-end (wallet funding, order payment, provisioning) against live XUI server.';

    public function handle(): int
    {
        $serverId = $this->option('server');
        $panel = $this->option('panel');
        $username = $this->option('username');
        $password = $this->option('password');
        $walletAmount = (float)$this->option('wallet');
        $sharedQty = max(1, (int)$this->option('shared-quantity'));
        $dedicatedQty = max(1, (int)$this->option('dedicated-quantity'));
    $reuseSharedPlanId = $this->option('shared-plan');
    $reuseDedicatedPlanId = $this->option('dedicated-plan');
    $reuseSharedSlug = $this->option('shared-plan-slug');
    $reuseDedicatedSlug = $this->option('dedicated-plan-slug');
    $outputJson = $this->option('json');
        $insecure = (bool)$this->option('insecure');
        $diagnostics = (bool)$this->option('diagnostics');
        $dryRun = (bool)$this->option('dry-run');
        $skipShared = (bool)$this->option('skip-shared');
        $skipDedicated = (bool)$this->option('skip-dedicated');
        $portMin = (int)$this->option('dedicated-port-min');
        $portMax = (int)$this->option('dedicated-port-max');

        if (!$serverId) {
            $this->error('--server is required');
            return 1;
        }

        $server = Server::find($serverId);
        if (!$server) {
            $this->error('Server not found');
            return 1;
        }
        if ($panel) {
            $panelTrim = rtrim($panel,'/');
            $parsed = parse_url($panelTrim);
            $hostParsed = $parsed['host'] ?? null;
            $portParsed = $parsed['port'] ?? null;
            $pathParsed = trim($parsed['path'] ?? '', '/');
            // Accept path containing 'panel' + optional extra segment (legacy) but last segment is web_base_path
            $webBase = $pathParsed ?: null;
            // Common patterns like /proxy or /panel or /panel/proxy -> choose last meaningful segment
            if ($webBase && str_contains($webBase, '/')) {
                $segments = array_values(array_filter(explode('/', $webBase)));
                $webBase = end($segments) ?: null;
            }
            $updates = ['panel_url' => $panelTrim];
            if ($hostParsed) { $updates['host'] = $hostParsed; }
            if ($portParsed) { $updates['panel_port'] = $portParsed; }
            if ($webBase) { $updates['web_base_path'] = $webBase; }
            $server->update($updates);
            $this->line('Updated server connection fields: ' . json_encode($updates));
        }
        if ($username) { $server->update(['username' => $username]); }
        if ($password) { $server->update(['password' => $password]); }

        if ($insecure) {
            app()->instance('xui.insecure', true);
            $this->warn('Insecure TLS mode ON');
        }
        app()->instance('provision.dedicated.port_min', $portMin);
        app()->instance('provision.dedicated.port_max', $portMax);

        // Login & sync
        $xui = new XUIService($server);
        if (!$xui->testConnection()) {
            $this->error('Login failed to XUI');
            return 1;
        }
        if (!$dryRun) {
            $synced = $xui->syncAllInbounds();
            $this->line("Synced {$synced} inbounds");
        } else {
            $this->line('Dry-run: skipped inbound sync');
        }

        // Customer + wallet
        $customer = Customer::factory()->create();
        if ($walletAmount > 0) {
            $customer->addToWallet($walletAmount, 'Initial Simulation Top-up');
        }
        $this->info("Customer #{$customer->id} wallet balance: " . $customer->getWallet()->balance);

        // Create or reuse plans
        $sharedPlan = null; $dedicatedPlan = null; $columns = Schema::getColumnListing('server_plans');
        if (!$skipShared) {
            if ($reuseSharedPlanId) {
                $sharedPlan = ServerPlan::find($reuseSharedPlanId);
            } elseif ($reuseSharedSlug) {
                $sharedPlan = ServerPlan::where('slug', $reuseSharedSlug)->first();
            }
            if (!$sharedPlan) {
                $sharedPlan = ServerPlan::create($this->buildPlanAttrs($server->id, 'multiple'));
            }
        }
        if (!$skipDedicated) {
            if ($reuseDedicatedPlanId) {
                $dedicatedPlan = ServerPlan::find($reuseDedicatedPlanId);
            } elseif ($reuseDedicatedSlug) {
                $dedicatedPlan = ServerPlan::where('slug', $reuseDedicatedSlug)->first();
            }
            if (!$dedicatedPlan) {
                $dedicatedPlan = ServerPlan::create($this->buildPlanAttrs($server->id, 'single'));
            }
        }

        $provisioner = app()->makeWith(ClientProvisioningService::class, ['xuiService' => new XUIService($server)]);
        $resultsSummary = [];

        if (!$skipShared && $sharedPlan) {
            $sharedOrder = $this->createPaidOrder($customer, $sharedPlan, $sharedQty, $dryRun);
            if ($sharedOrder) {
                $this->line("Processing shared order #{$sharedOrder->id} (qty={$sharedQty})");
                if (!$dryRun) {
                    $sharedResults = $provisioner->provisionOrder($sharedOrder->fresh('items.serverPlan'));
                    $resultsSummary['shared'] = $sharedResults;
                }
            }
        }
        if (!$skipDedicated && $dedicatedPlan) {
            for ($i=0; $i < $dedicatedQty; $i++) {
                $dedicatedOrder = $this->createPaidOrder($customer, $dedicatedPlan, 1, $dryRun);
                if ($dedicatedOrder) {
                    $this->line("Processing dedicated order #{$dedicatedOrder->id}");
                    if (!$dryRun) {
                        $dedicatedResults = $provisioner->provisionOrder($dedicatedOrder->fresh('items.serverPlan'));
                        $resultsSummary['dedicated'][] = $dedicatedResults;
                    }
                }
            }
        }

        // Output result tables
        if ($outputJson && !$dryRun) {
            $jsonOut = [];
            foreach ($resultsSummary as $type => $data) {
                $jsonOut[$type] = $data;
            }
            $this->line(json_encode(['results' => $jsonOut], JSON_PRETTY_PRINT));
        } elseif (!$dryRun) {
            foreach ($resultsSummary as $type => $data) {
                $blocks = [];
                // Normalize shapes: could be a direct result array, a keyed map of order_item_id => result, or an array of those maps
                $candidateSets = is_array($data) ? $data : [];
                // If data itself has order_item_id treat as single block
                if (isset($data['order_item_id'])) {
                    $candidateSets = [ $data ];
                }
                foreach ($candidateSets as $candidate) {
                    if (!is_array($candidate)) continue;
                    // Direct block
                    if (isset($candidate['order_item_id'])) {
                        $blocks[] = $candidate;
                        continue;
                    }
                    // Possibly map of id => block
                    foreach ($candidate as $maybe) {
                        if (is_array($maybe) && isset($maybe['order_item_id'])) {
                            $blocks[] = $maybe;
                        }
                    }
                }
                $this->info(strtoupper($type) . ' RESULTS');
                if (empty($blocks)) {
                    $this->warn('No result blocks captured');
                }
                foreach ($blocks as $block) {
                    $this->table(['Order Item ID','Plan','Requested','Provisioned','Client Count'], [[
                        $block['order_item_id'] ?? '-',
                        $block['plan_name'] ?? '-',
                        $block['quantity_requested'] ?? '-',
                        $block['quantity_provisioned'] ?? '-',
                        isset($block['clients']) ? count($block['clients']) : '-',
                    ]]);
                }
            }
        } else {
            $this->info('Dry-run complete (no provisioning).');
        }

        $this->info('Simulation finished.');
        return 0;
    }

    protected function buildPlanAttrs(int $serverId, string $type): array
    {
        return [
            'server_id' => $serverId,
            'name' => 'Amsterdam Datacenter Proxy ' . ($type === 'single' ? 'Dedicated' : 'Shared') . ' ' . Str::upper(Str::random(3)),
            'slug' => 'ams-dc-proxy-' . ($type === 'single' ? 'dedicated' : 'shared') . '-' . Str::lower(Str::random(3)),
            'price' => 10.00,
            'type' => $type,
            'days' => 30,
            'volume' => 50,
            'is_active' => true,
            'in_stock' => true,
            'on_sale' => true,
            'server_category_id' => 1,
            'server_brand_id' => 1,
            'max_clients' => $type === 'single' ? 1 : 100,
        ];
    }

    protected function createPaidOrder(Customer $customer, ServerPlan $plan, int $quantity, bool $dryRun): ?Order
    {
        // Wallet payment
        $cost = $plan->price * $quantity;
        if ($customer->getWallet()->balance < $cost) {
            $this->error("Insufficient wallet for order (need {$cost})");
            return null;
        }
        if (!$dryRun) {
            $customer->payFromWallet($cost, 'Simulated Order Purchase');
        }
        $order = Order::create([
            'customer_id' => $customer->id,
            'grand_amount' => $cost,
            'currency' => 'USD',
            'payment_status' => 'paid',
            'order_status' => 'new',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'server_plan_id' => $plan->id,
            'quantity' => $quantity,
            'total_amount' => $cost,
            'unit_amount' => $plan->price,
        ]);
        return $order->fresh('items.serverPlan');
    }
}
