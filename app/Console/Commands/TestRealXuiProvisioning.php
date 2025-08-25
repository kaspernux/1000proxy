<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Server, ServerPlan, Order, OrderItem, Customer};
use App\Services\{XUIService, ClientProvisioningService};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TestRealXuiProvisioning extends Command
{
    protected $signature = 'xui:test-provision 
        {--panel= : Full base panel URL e.g. https://host:1111/proxy} 
        {--username= : Panel username} 
        {--password= : Panel password} 
        {--mode=shared : Provision mode: shared|dedicated maps to plan type multiple|single} 
        {--plan= : Reuse existing plan ID instead of creating} 
        {--server= : Reuse existing server ID instead of creating} 
    {--quantity=1 : Number of clients to provision for the plan/order item}
    {--cleanup-minutes=30 : Auto-delete prior test plans/inbounds older than N minutes}
    {--keep-dedicated : Keep dedicated inbound even if post-creation health check fails}
    {--dedicated-port-min=20000 : Minimum port for dedicated inbound allocation}
    {--dedicated-port-max=60000 : Maximum port for dedicated inbound allocation}
    {--single-port-min=60001 : Minimum port for single inbound allocation}
    {--single-port-max=70000 : Maximum port for single inbound allocation}
    {--dry-run : Show what would happen without remote mutations (still logs in & lists inbounds)} 
    {--diagnostics : Show detailed diagnostic output} 
    {--insecure : Disable TLS certificate verification for debugging (NOT for production)} 
    {--unlock : Force clear login lock counters before attempting}
    {--debug-body : Log extended HTTP body snippets (for troubleshooting auth)}';

    protected $description = 'Perform a one-off live provisioning test against a real XUI panel (DO NOT use in production unattended).';

    public function handle(): int
    {
        $panel = rtrim($this->option('panel') ?? '', '/');
        $username = $this->option('username');
        $password = $this->option('password');
    $mode = strtolower($this->option('mode') ?? 'shared');
        $useServerId = $this->option('server');
        $usePlanId = $this->option('plan');
    $quantity = max(1, (int) $this->option('quantity'));
    $cleanupMinutes = (int) $this->option('cleanup-minutes');
    $keepDedicated = (bool) $this->option('keep-dedicated');
    $dedicatedPortMin = (int) $this->option('dedicated-port-min');
    $dedicatedPortMax = (int) $this->option('dedicated-port-max');
    $dryRun = (bool) $this->option('dry-run');
    $diagnostics = (bool) $this->option('diagnostics');
    $insecure = (bool) $this->option('insecure');
    $unlock = (bool) $this->option('unlock');
    $debugBody = (bool) $this->option('debug-body');

        if (!$panel || !$username || !$password) {
            $this->error('panel, username and password options are required.');
            return 1;
        }
        if (!in_array($mode, ['shared','dedicated','single','multiple'])) {
            $this->error('--mode must be shared, dedicated, single, or multiple');
            return 1;
        }

        $this->warn('This will contact the real XUI panel: ' . $panel);
        if ($dryRun) {
            $this->info('Dry run enabled: remote create (inbound / client) steps will be skipped.');
        }

        // 1. Resolve or create server
        $server = null;
        if ($useServerId) {
            $server = Server::find($useServerId);
            if (!$server) { $this->error('Server ID not found'); return 1; }
        } else {
            $server = Server::firstOrCreate([
                'panel_url' => $panel,
            ], [
                'name' => 'LIVE-XUI-' . Str::upper(Str::random(5)),
                'username' => $username,
                'password' => $password,
                'status' => 'up',
                'auto_provisioning' => true,
                'country' => 'NL',
                'health_status' => 'unknown',
                'server_category_id' => 1,
                'server_brand_id' => 1,
                'ip' => parse_url($panel, PHP_URL_HOST) ?? '127.0.0.1',
                'host' => parse_url($panel, PHP_URL_HOST) ?? 'localhost',
                'panel_port' => parse_url($panel, PHP_URL_PORT) ?? 443,
                'port' => parse_url($panel, PHP_URL_PORT) ?? 443,
                'type' => 'xui',
            ]);
            // Always update credentials in case changed
            $server->update(['username' => $username, 'password' => $password]);
            // Derive web base path from provided panel URL if not explicitly set
            $path = trim(parse_url($panel, PHP_URL_PATH) ?? '', '/');
            if ($path && !$server->web_base_path) {
                $server->update(['web_base_path' => $path]);
            }
        }

        $this->line('Using Server ID: ' . $server->id);
        if ($insecure) {
            app()->instance('xui.insecure', true);
            $this->warn('TLS verification disabled (insecure mode ON).');
        }
        // Bind provisioning config overrides for dedicated mode
        app()->instance('provision.dedicated.port_min', $dedicatedPortMin);
        app()->instance('provision.dedicated.port_max', $dedicatedPortMax);
        if ($keepDedicated) {
            app()->instance('provision.keep_dedicated', true);
        }
        if ($debugBody) {
            app()->instance('xui.debug_body', true);
            $this->warn('Extended body logging enabled (--debug-body). DO NOT use in production routinely.');
        }
        if ($unlock && $server->login_attempts > 0) {
            $server->update(['login_attempts' => 0, 'last_login_attempt_at' => null]);
            $this->info('Login attempt counters reset (--unlock).');
        }
    if ($diagnostics) {
            $this->info('Derived attributes:');
            $this->line('  host=' . $server->host);
            $this->line('  panel_port=' . $server->panel_port);
            $this->line('  web_base_path=' . ($server->web_base_path ?? '(none)'));
            $this->line('  api_base=' . $server->getApiBaseUrl());
            $this->line('  login_endpoint=' . $server->getApiEndpoint('login'));
        }

        // 2a. Optional cleanup of stale test artifacts
        if ($cleanupMinutes > 0) {
            $this->cleanupStaleArtifacts($cleanupMinutes, $diagnostics);
        }

        // 2b. Login & sync inbounds
        $xui = new XUIService($server);
        if (!$xui->testConnection()) {
            $this->error('Login failed. Check credentials.');
            return 1;
        }
        $this->info('Login succeeded. Synchronising inbounds...');
        $count = $dryRun ? 0 : $xui->syncAllInbounds();
        if (!$dryRun) {
            $this->line("Synced {$count} inbounds (local DB)");
        }

        // For shared mode we still need at least one base inbound; for dedicated a base inbound is still required to clone settings
        $baseInbound = $server->inbounds()->orderBy('port')->first();
        if (!$baseInbound) {
            $this->error('No inbounds available after sync to use as base. Aborting.');
            return 1;
        }
        $this->line('Base inbound #' . $baseInbound->id . ' port ' . $baseInbound->port . ' will be used for cloning (shared or dedicated).');

        if (!$baseInbound->provisioning_enabled || $baseInbound->status !== 'active') {
            $baseInbound->update([
                'provisioning_enabled' => true,
                'status' => 'active',
            ]);
            $this->line('Base inbound provisioning flags normalized (provisioning_enabled=1, status=active).');
        }
        if (!$server->inbounds()->where('is_default', true)->exists()) {
            $baseInbound->update(['is_default' => true]);
            $this->line('Marked base inbound #' . $baseInbound->id . ' as default (no previous default).');
        }

        // 3. Resolve or create plan
        $plan = null;
        if ($usePlanId) {
            $plan = ServerPlan::find($usePlanId);
            if (!$plan) { $this->error('Plan ID not found'); return 1; }
        } else {
            $planType = $mode === 'dedicated' ? 'single' : 'multiple';
            $columns = Schema::getColumnListing('server_plans');
            $attrs = [
                'server_id' => $server->id,
                // SEO-friendly test name for clarity in logs and UI
                'name' => 'Amsterdam Datacenter Proxy ' . ($mode === 'dedicated' ? 'Dedicated' : 'Shared') . ' ' . Str::upper(Str::random(3)),
                'slug' => 'ams-dc-proxy-' . ($mode === 'dedicated' ? 'dedicated' : 'shared') . '-' . Str::lower(Str::random(3)),
                'price' => 5.00,
                'type' => $planType,
                'days' => 30,
                'volume' => 50,
                'is_active' => true,
                'in_stock' => true,
                'on_sale' => true,
                'server_category_id' => 1,
                'server_brand_id' => 1,
            ];
            // Only set preferred inbound for shared (multi-client) plans; dedicated plans always create a fresh inbound
            if ($planType !== 'single' && in_array('preferred_inbound_id', $columns, true)) {
                $attrs['preferred_inbound_id'] = $baseInbound->id;
            }
            if (in_array('auto_provision', $columns, true)) $attrs['auto_provision'] = true;
            if (in_array('data_limit_gb', $columns, true)) $attrs['data_limit_gb'] = 50;
            if (in_array('current_clients', $columns, true)) $attrs['current_clients'] = 0;
            if (in_array('max_clients', $columns, true)) $attrs['max_clients'] = ($mode === 'dedicated' ? 1 : 100);
            if (in_array('auto_provisioning', $columns, true)) $attrs['auto_provisioning'] = true; // legacy compatibility
            $plan = ServerPlan::create($attrs);
        }
    $this->line('Using Plan ID: ' . $plan->id . ' (type=' . $plan->type . ', quantity=' . $quantity . ')');

        // 4. Create order + customer + item (schema-aware)
        $customer = Customer::factory()->create();

        $orderColumns = Schema::getColumnListing('orders');
        $orderAttributes = [
            'customer_id' => $customer->id,
        ];
        // Monetary amount mapping
        if (in_array('grand_amount', $orderColumns, true)) {
            $orderAttributes['grand_amount'] = $plan->price;
        } elseif (in_array('total_amount', $orderColumns, true)) {
            $orderAttributes['total_amount'] = $plan->price;
        } elseif (in_array('amount', $orderColumns, true)) {
            $orderAttributes['amount'] = $plan->price;
        }
        if (in_array('currency', $orderColumns, true)) {
            $orderAttributes['currency'] = 'USD';
        }
        if (in_array('payment_status', $orderColumns, true)) {
            $orderAttributes['payment_status'] = 'paid';
        }
        if (in_array('order_status', $orderColumns, true)) {
            $orderAttributes['order_status'] = 'new';
        } elseif (in_array('status', $orderColumns, true)) {
            // fallback older schema
            $orderAttributes['status'] = 'new';
        }
        $order = Order::create($orderAttributes);

        $itemColumns = Schema::getColumnListing('order_items');
        $itemAttributes = [
            'order_id' => $order->id,
            'server_plan_id' => $plan->id,
            'quantity' => $quantity,
        ];
        if (in_array('unit_amount', $itemColumns, true)) {
            $itemAttributes['unit_amount'] = $plan->price;
        }
        if (in_array('total_amount', $itemColumns, true)) {
            $itemAttributes['total_amount'] = $plan->price;
        } elseif (in_array('total', $itemColumns, true)) {
            $itemAttributes['total'] = $plan->price; // legacy
        }
        OrderItem::create($itemAttributes);

        $this->info('Order #' . $order->id . ' created for Customer #' . $customer->id);

        if ($dryRun) {
            $this->info('Dry run complete (no provisioning attempted).');
            return 0;
        }

        // 5. Provision
        /** @var ClientProvisioningService $provisioner */
        // Rebind provisioning service with a server-specific XUIService instance to ensure remote calls are executed.
        $provisioner = app()->makeWith(ClientProvisioningService::class, [
            'xuiService' => new XUIService($server),
        ]);
    $results = $provisioner->provisionOrder($order->fresh('items.serverPlan'));

        // Extract dedicated inbound IDs (if any) from results for clearer summary
        $dedicatedIds = collect($results)
            ->flatMap(fn($r) => $r['clients'] ?? [])
            ->filter(fn($c) => ($c['dedicated_inbound_id'] ?? null))
            ->pluck('dedicated_inbound_id')
            ->unique()
            ->values()
            ->all();
        $summaryInbound = ($mode === 'dedicated') ? (implode(',', $dedicatedIds) ?: '(none-created)') : $baseInbound->id;

        $this->table(['Key','Value'], [
            ['order_id', $order->id],
            ['mode', $mode],
            ['plan_id', $plan->id],
            ['inbound_id', $summaryInbound],
            ['quantity', $quantity],
            ['results', json_encode($results)],
        ]);

        $this->info('Provisioning complete. Check logs for detailed steps.');
        return 0;
    }

    /**
     * Delete test plans & dedicated inbounds older than N minutes (zero clients only for inbounds)
     */
    protected function cleanupStaleArtifacts(int $minutes, bool $verbose = false): void
    {
        $cutoff = now()->subMinutes($minutes);
        $planQuery = \App\Models\ServerPlan::where('name', 'like', 'Test Plan %')
            ->where('created_at', '<', $cutoff);
        $stalePlans = $planQuery->count();
        $planQuery->delete();

        $inboundQuery = \App\Models\ServerInbound::where('remark', 'like', 'DEDICATED%')
            ->where('created_at', '<', $cutoff)
            ->where(function($q){ $q->whereNull('current_clients')->orWhere('current_clients', 0); });
        $staleInbounds = $inboundQuery->count();
        $inboundQuery->delete();

        if ($verbose) {
            $this->line("Cleanup: removed {$stalePlans} stale test plans & {$staleInbounds} dedicated inbounds older than {$minutes}m.");
        }
    }
}
