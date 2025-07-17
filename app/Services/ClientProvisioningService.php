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

class ClientProvisioningService
{
    protected XUIService $xuiService;
    protected int $maxRetries = 3;
    protected int $retryDelay = 5; // seconds

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

        // Initialize XUI Service
        $this->xuiService = new XUIService($server);

        // Get best inbound for provisioning
        $inbound = $this->getBestInbound($plan);
        if (!$inbound) {
            return $this->createFailureResult('No suitable inbound available');
        }

        // Create order-client tracking record
        $orderClient = new OrderServerClient([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'provision_status' => 'pending',
        ]);

        try {
            // Start provisioning
            $orderClient->markProvisionStarted([
                'plan_id' => $plan->id,
                'inbound_id' => $inbound->id,
                'client_number' => $clientNumber,
                'provision_settings' => $plan->getProvisioningSettings(),
            ]);

            // Generate client configuration
            $clientConfig = $this->generateClientConfig($plan, $order, $clientNumber);

            // Create client on remote XUI panel
            $remoteClient = $this->createRemoteClient($inbound, $clientConfig);

            // Create local client record
            $serverClient = $this->createLocalClient($inbound, $remoteClient, $plan, $order);

            // Update order-client relationship
            $orderClient->update(['server_client_id' => $serverClient->id]);
            $orderClient->markProvisionCompleted([
                'remote_client_data' => $remoteClient,
                'server_client_id' => $serverClient->id,
            ]);

            // Update counters
            $this->updateCounters($plan, $inbound, 1);

            Log::info("✅ Successfully provisioned client for Order #{$order->id}, Item #{$item->id}");

            return [
                'success' => true,
                'server_client_id' => $serverClient->id,
                'client_config' => $serverClient->getDownloadableConfig(),
                'provision_duration' => $orderClient->provision_duration_seconds,
            ];

        } catch (\Exception $e) {
            $orderClient->markProvisionFailed($e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
    protected function generateClientConfig(ServerPlan $plan, Order $order, int $clientNumber): array
    {
        $customer = $order->customer;

        return [
            'id' => $this->xuiService->generateUID(),
            'email' => "{$plan->name} - Client #{$clientNumber} - Order #{$order->id} - ID {$customer->id}",
            'limit_ip' => $plan->provision_settings['connection_limit'] ?? 2,
            'totalGB' => ($plan->data_limit_gb ?? $plan->volume) * 1073741824, // Convert GB to bytes
            'expiry_time' => now()->addDays($plan->days + ($plan->trial_days ?? 0))->timestamp * 1000,
            'enable' => true,
            'flow' => 'xtls-rprx-vision',
            'tg_id' => $customer->telegram_id ?? '',
            'subId' => \Illuminate\Support\Str::random(16),
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
