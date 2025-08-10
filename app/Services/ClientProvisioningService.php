<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Models\OrderServerClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClientProvisioningService
{
    protected XUIService $xuiService;
    protected int $maxRetries = 3;
    protected int $retryDelay = 5; // seconds

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService; // Will be rebound per server in provisionSingleClient
    }

    /**
     * Provision clients for an order
     */
    public function provisionOrder(Order $order): array
    {
        Log::info("🚀 Starting enhanced client provisioning for Order #{$order->id}");

        $results = [];

        DB::transaction(function () use ($order, &$results) {
            foreach ($order->items as $item) {
                $results[$item->id] = $this->provisionOrderItem($item);
            }
        });

        // Update order status based on results
        $this->updateOrderStatus($order, $results);

        Log::info("✅ Enhanced client provisioning completed for Order #{$order->id}", $results);
        return $results;
    }

    /**
     * Provision clients for a single order item
     */
    protected function provisionOrderItem(OrderItem $item): array
    {
        $plan = $item->serverPlan;
        $quantity = $item->quantity;

        Log::info("📦 Provisioning {$quantity} client(s) for plan: {$plan->name}");

        // Pre-provision checks
        if (!$this->preProvisionChecks($plan, $quantity)) {
            return $this->createFailureResult('Pre-provision checks failed');
        }

        $results = [];

        for ($i = 0; $i < $quantity; $i++) {
            try {
                $result = $this->provisionSingleClient($item, $i + 1);
                $results[] = $result;

                if (!$result['success']) {
                    Log::warning("❌ Failed to provision client " . (($i + 1)) . " for order item " . $item->id);
                }
            } catch (\Exception $e) {
                Log::error("💥 Exception during client provisioning", [
                    'order_item_id' => $item->id,
                    'client_number' => $i + 1,
                    'error' => $e->getMessage(),
                ]);

                $results[] = $this->createFailureResult($e->getMessage());
            }
        }

        return [
            'order_item_id' => $item->id,
            'plan_name' => $plan->name,
            'quantity_requested' => $quantity,
            'quantity_provisioned' => collect($results)->where('success', true)->count(),
            'clients' => $results,
        ];
    }

    /**
     * Provision a single client
     */
    protected function provisionSingleClient(OrderItem $item, int $clientNumber): array
    {
        $plan = $item->serverPlan;
        $order = $item->order;
        $server = $plan->server;

        // Rebind XUI service to target server (supports test fakes via container)
        $this->xuiService = app()->makeWith(XUIService::class, ['server' => $server]);

    // Determine provisioning mode (shared vs dedicated) and resolve inbound
        $mode = $this->determineProvisionMode($plan);

        // Reuse a previously created dedicated inbound for this order_item if exists
        $existingDedicatedInboundId = null;
        if ($mode === 'dedicated') {
            $existingDedicatedInboundId = OrderServerClient::where('order_id', $order->id)
                ->where('order_item_id', $item->id)
                ->whereNotNull('dedicated_inbound_id')
                ->value('dedicated_inbound_id');
        }

        if ($existingDedicatedInboundId) {
            $inbound = ServerInbound::find($existingDedicatedInboundId);
            Log::info('🔁 Reusing existing dedicated inbound for order item', [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'inbound_id' => $existingDedicatedInboundId,
            ]);
        } else {
            $inbound = $this->resolveInbound($plan, $order, $mode);
        }
        if (!$inbound) {
            return $this->createFailureResult('No suitable inbound available');
        }

        try {
            $startedAt = now();
            $configMeta = [
                'plan_id' => $plan->id,
                'inbound_id' => $inbound->id,
                'dedicated_inbound_id' => $mode === 'dedicated' ? $inbound->id : null,
                'client_number' => $clientNumber,
                'provision_settings' => $plan->getProvisioningSettings(),
            ];

            // Generate client configuration (enhanced naming for branding / categorisation)
            $clientConfig = $this->generateClientConfig($plan, $order, $clientNumber, $mode, $inbound);

            // Create client on remote XUI panel
            $remoteClient = $this->createRemoteClient($inbound, $clientConfig);

            // Create local client record
            $serverClient = $this->createLocalClient($inbound, $remoteClient, $plan, $order);

            // Persist order-server-client tracking record (completed state)
            $orderClient = OrderServerClient::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'server_client_id' => $serverClient->id,
                'server_inbound_id' => $inbound->id,
                'dedicated_inbound_id' => $mode === 'dedicated' ? $inbound->id : null,
                'provision_status' => 'completed',
                'provision_attempts' => 1,
                'provision_started_at' => $startedAt,
                'provision_completed_at' => now(),
                'provision_duration_seconds' => isset($startedAt) ? now()->diffInSeconds($startedAt, true) : null,
                'provision_config' => $configMeta,
                'provision_log' => [
                    'remote_client_data' => $remoteClient,
                    'server_client_id' => $serverClient->id,
                    'inbound_id' => $inbound->id,
                    'dedicated_inbound_id' => $mode === 'dedicated' ? $inbound->id : null,
                ],
            ]);

            // Update counters
            $this->updateCounters($plan, $inbound, 1);

            Log::info("✅ Successfully provisioned client for Order #{$order->id}, Item #{$item->id} ({$mode})", [
                'mode' => $mode,
                'inbound_id' => $inbound->id,
                'server_client_id' => $serverClient->id,
            ]);

            return [
                'success' => true,
                'server_client_id' => $serverClient->id,
                'client_config' => $serverClient->getDownloadableConfig(),
                'provision_duration' => $orderClient->provision_duration_seconds,
            ];

    } catch (\Throwable $e) {
            try {
                OrderServerClient::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'server_inbound_id' => $inbound->id ?? null,
                    'dedicated_inbound_id' => $mode === 'dedicated' && isset($inbound) ? $inbound->id : null,
                    'provision_status' => 'failed',
                    'provision_attempts' => 1,
                    'provision_started_at' => $startedAt ?? now(),
                    'provision_completed_at' => now(),
                    'provision_config' => $configMeta ?? [],
                    'provision_log' => [
                        'exception' => $e->getMessage(),
                    ],
                    'provision_error' => $e->getMessage(),
                ]);
            } catch (\Throwable $logFailed) {
                // swallow secondary failure
            }

            Log::error("❌ Failed to provision client", [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return $this->createFailureResult($e->getMessage());
        }
    }

    /**
     * Pre-provision checks
     */
    protected function preProvisionChecks(ServerPlan $plan, int $quantity): bool
    {
        // Check if plan is available
        if (!$plan->isAvailable()) {
            Log::error("❌ Plan {$plan->name} is not available for provisioning");
            return false;
        }

        // Check capacity
        if (!$plan->hasCapacity($quantity)) {
            Log::error("❌ Plan {$plan->name} does not have capacity for {$quantity} clients");
            return false;
        }

        // Check server health
        if (!$plan->server->canProvision($quantity)) {
            Log::error("❌ Server {$plan->server->name} cannot provision {$quantity} clients");
            return false;
        }

        return true;
    }

    /**
     * Get best inbound for provisioning
     */
    protected function getBestInbound(ServerPlan $plan): ?ServerInbound
    {
        // Try plan's preferred inbound first
        $inbound = $plan->getBestInbound();

        if (!$inbound) {
            // Fall back to server's best inbound
            $inbound = $plan->server->getBestInboundForProvisioning();
        }

        if (!$inbound) {
            Log::error("❌ No suitable inbound found for plan {$plan->name}");
            return null;
        }

        Log::info("📡 Selected inbound #{$inbound->id} (port {$inbound->port}) for provisioning");
        return $inbound;
    }

    /**
     * Generate client configuration
     */
    protected function generateClientConfig(ServerPlan $plan, Order $order, int $clientNumber, string $mode = 'shared', ?ServerInbound $inbound = null): array
    {
        $customer = $order->customer;
        $brandCode = strtoupper(Str::limit($plan->brand?->slug ?? 'GEN', 6, ''));
        $categoryCode = strtoupper(Str::limit($plan->category?->slug ?? 'GEN', 6, ''));
        $shortPlan = strtoupper(Str::limit(preg_replace('/[^A-Za-z0-9]/', '', $plan->slug ?? $plan->name), 8, ''));
        $rand = Str::upper(Str::random(4));
        $modeFlag = $mode === 'dedicated' ? 'D' : 'S';
        $inboundTag = $inbound?->tag ?? $inbound?->id;

        // Canonical client identifier (used in email field for XUI uniqueness + internal searching)
        $identifier = join('-', array_filter([
            $brandCode,
            $categoryCode,
            $shortPlan,
            'O' . $order->id,
            'C' . $customer->id,
            'N' . $clientNumber,
            $modeFlag,
            $rand,
        ]));

        return [
            'id' => $this->xuiService->generateUID(),
            'email' => $identifier,
            'limit_ip' => $plan->provision_settings['connection_limit'] ?? 2,
            'totalGB' => ($plan->data_limit_gb ?? $plan->volume) * 1073741824, // Convert GB to bytes
            'expiry_time' => now()->addDays($plan->days + ($plan->trial_days ?? 0))->timestamp * 1000,
            'enable' => true,
            'flow' => 'xtls-rprx-vision',
            'tg_id' => $customer->telegram_id ?? '',
            'subId' => Str::random(16),
        ];
    }

    /**
     * Create client on remote XUI panel
     */
    protected function createRemoteClient(ServerInbound $inbound, array $clientConfig): array
    {
        $settings = json_encode([
            'clients' => [$clientConfig]
        ]);

        $success = $this->xuiService->addClient($inbound->remote_id, $settings);

        if (!$success) {
            throw new \Exception('Failed to create client on remote XUI panel');
        }

        // Return the client data with generated links
        return array_merge($clientConfig, [
            'link' => ServerClient::buildXuiClientLink($clientConfig, $inbound, $inbound->server),
            'sub_link' => "https://{$inbound->server->getPanelHost()}:{$inbound->server->getSubscriptionPort()}/sub/{$clientConfig['subId']}",
            'json_link' => "https://{$inbound->server->getPanelHost()}:{$inbound->server->getSubscriptionPort()}/json/{$clientConfig['subId']}",
        ]);
    }

    /**
     * Create local client record
     */
    protected function createLocalClient(ServerInbound $inbound, array $remoteClient, ServerPlan $plan, Order $order): ServerClient
    {
        $serverClient = ServerClient::fromRemoteClient($remoteClient, $inbound->id, $remoteClient['link']);

        // Update with order and customer associations
        $serverClient->update([
            'plan_id' => $plan->id,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'status' => 'active',
            'provisioned_at' => now(),
            'activated_at' => now(),
            'traffic_limit_mb' => ($plan->data_limit_gb ?? $plan->volume) * 1024,
            'auto_renew' => $plan->renewable,
            'renewal_price' => $plan->price,
            'next_billing_at' => now()->addDays($plan->days)->subDays(7), // 7 days before expiry
        ]);

        return $serverClient;
    }

    /* ========================= NEW SHARED vs DEDICATED LOGIC ========================= */

    /**
     * Determine provisioning mode based on plan attributes.
     * 'single' plan type => dedicated inbound per order (or item), else shared.
     */
    protected function determineProvisionMode(ServerPlan $plan): string
    {
        try {
            if (strtolower($plan->type) === 'single') {
                return 'dedicated';
            }
        } catch (\Throwable $e) {
            // Fallback silently
        }
        return 'shared';
    }

    /**
     * Resolve inbound depending on provisioning mode.
     */
    protected function resolveInbound(ServerPlan $plan, Order $order, string $mode): ?ServerInbound
    {
        if ($mode === 'dedicated') {
            // Attempt to create a fresh inbound dedicated to this order & plan.
            $inbound = $this->createDedicatedInbound($plan, $order);
            if ($inbound) {
                return $inbound;
            }
            Log::warning('Falling back to shared inbound (dedicated creation failed)', [
                'order_id' => $order->id,
                'plan_id' => $plan->id,
            ]);
        }
        return $this->getBestInbound($plan);
    }

    /**
     * Create a dedicated inbound for a single-type plan.
     * Strategy: clone a base inbound (preferred or server default) and adjust port + remark.
     * If creation fails, returns null (caller will fallback to shared mode).
     */
    protected function createDedicatedInbound(ServerPlan $plan, Order $order): ?ServerInbound
    {
        // replace naive port selection with transactional allocator
        $server = $plan->server;
        $base = $plan->getBestInbound() ?: $server->getBestInboundForProvisioning();
        if (!$base) {
            Log::error('No base inbound available for dedicated creation', [
                'order_id' => $order->id,
                'plan_id' => $plan->id,
            ]);
            return null;
        }

        try {
            $placeholder = $this->allocateAvailablePort($server);
            if (!$placeholder) {
                return null;
            }
            $port = $placeholder->port;
            $remark = 'DEDICATED O' . $order->id . ' P' . $plan->id . ' ' . Str::upper(Str::random(4));
            $tag = 'dedic-' . $order->id . '-' . $plan->id . '-' . Str::lower(Str::random(5));

            // Build minimal inbound payload (clone essential fields)
            $baseStream = is_array($base->streamSettings) ? $base->streamSettings : json_decode($base->streamSettings ?? '{}', true);
            $payload = [
                'up' => 0,
                'down' => 0,
                'total' => 0,
                'remark' => $remark,
                'enable' => true,
                'expiry_time' => 0,
                'listen' => $base->listen ?? '',
                'port' => $port,
                'protocol' => $base->protocol,
                'settings' => json_encode(['clients' => []]),
                'streamSettings' => json_encode($baseStream),
                'tag' => $tag,
                'sniffing' => json_encode([]),
                'allocate' => json_encode([]),
            ];

            $remote = $this->xuiService->createInbound($payload);
            if (!$remote || empty($remote['id'] ?? null)) {
                Log::error('Failed to create dedicated inbound via API', [
                    'order_id' => $order->id,
                    'plan_id' => $plan->id,
                    'payload' => $payload,
                    'response' => $remote,
                ]);
                // Cleanup placeholder to free port for future attempts
                try { $placeholder->delete(); } catch (\Throwable $eDel) {
                    Log::warning('Failed deleting placeholder after remote creation failure', [
                        'placeholder_id' => $placeholder->id,
                        'error' => $eDel->getMessage(),
                    ]);
                }
                return null;
            }

            // Persist locally using fromRemoteInbound helper (expects object)
            $remoteObject = (object) array_merge($remote, [
                'port' => $port,
                'id' => $remote['id'] ?? null,
                'remote_id' => $remote['id'] ?? null,
            ]);
            $local = ServerInbound::fromRemoteInbound($remoteObject, $server->id);

            // Mark dedicated attributes locally
            $local->update([
                'provisioning_enabled' => true,
                'is_default' => false,
                'capacity' => $plan->max_clients ?? 1,
                'current_clients' => 0,
                'status' => 'active',
                'remark' => $remark,
                'tag' => $tag,
            ]);

            Log::info('Created dedicated inbound', [
                'inbound_id' => $local->id,
                'server_id' => $server->id,
                'order_id' => $order->id,
                'plan_id' => $plan->id,
            ]);

            return $local;
        } catch (\Throwable $e) {
            Log::error('Exception creating dedicated inbound', [
                'order_id' => $order->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Allocate a free port in a concurrency-safe way.
     */
    protected function allocateAvailablePort($server, ?int $min = null, ?int $max = null, int $maxAttempts = 50): ?ServerInbound
    {
        $min = $min ?? (int) config('provisioning.dedicated_inbound_port_min', 20000);
        $max = $max ?? (int) config('provisioning.dedicated_inbound_port_max', 60000);

        return DB::transaction(function () use ($server, $min, $max, $maxAttempts) {
            $used = $server->inbounds()->lockForUpdate()->pluck('port')->filter()->toArray();
            $attempts = 0;
            $candidate = null;
            do {
                $candidate = random_int($min, $max);
                $attempts++;
            } while (in_array($candidate, $used, true) && $attempts < $maxAttempts);

            if (!$candidate || in_array($candidate, $used, true)) {
                Log::warning('Port allocator exhausted attempts', [
                    'server_id' => $server->id,
                    'attempts' => $attempts,
                ]);
                return null;
            }

            return ServerInbound::create([
                'server_id' => $server->id,
                'port' => $candidate,
                'protocol' => 'vless',
                'remark' => 'DEDICATED-RESERVE-' . Str::random(6),
                'enable' => false,
                'expiry_time' => 0,
            ]);
        }, 3);
    }

    /**
     * Update counters after successful provisioning
     */
    protected function updateCounters(ServerPlan $plan, ServerInbound $inbound, int $count): void
    {
        $plan->incrementClients($count);
        $inbound->incrementClients($count);
        $plan->server->updateStatistics();
    }

    /**
     * Update order status based on provisioning results
     */
    protected function updateOrderStatus(Order $order, array $results): void
    {
        $totalRequested = collect($results)->sum('quantity_requested');
        $totalProvisioned = collect($results)->sum('quantity_provisioned');

        if ($totalProvisioned === $totalRequested) {
            $order->markAsCompleted();
            Log::info("✅ Order #{$order->id} fully provisioned ({$totalProvisioned}/{$totalRequested} clients)");
        } elseif ($totalProvisioned > 0) {
            $order->updateStatus('processing');
            Log::warning("⚠️ Order #{$order->id} partially provisioned ({$totalProvisioned}/{$totalRequested} clients)");
        } else {
            $order->updateStatus('dispute');
            Log::error("❌ Order #{$order->id} provisioning failed (0/{$totalRequested} clients)");
        }
    }

    /**
     * Create failure result
     */
    protected function createFailureResult(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Retry failed provisions
     */
    public function retryFailedProvisions(Order $order): array
    {
        $failedProvisions = OrderServerClient::where('order_id', $order->id)
            ->where('provision_status', 'failed')
            ->where('provision_attempts', '<', $this->maxRetries)
            ->get();

        $results = [];

        foreach ($failedProvisions as $provision) {
            try {
                Log::info("🔄 Retrying provision for Order #{$order->id}, attempt #" . ($provision->provision_attempts + 1));

                $item = $provision->orderItem;
                $result = $this->provisionSingleClient($item, 1);

                $results[] = [
                    'provision_id' => $provision->id,
                    'retry_result' => $result,
                ];

            } catch (\Exception $e) {
                Log::error("❌ Retry failed for provision #{$provision->id}: " . $e->getMessage());

                $results[] = [
                    'provision_id' => $provision->id,
                    'retry_result' => $this->createFailureResult($e->getMessage()),
                ];
            }
        }

        return $results;
    }
}
