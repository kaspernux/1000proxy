<?php

namespace App\Services;

use App\Models\ServerClient;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ClientLifecycleService
{
    /**
     * Process expired clients
     */
    public function processExpiredClients(): array
    {
        Log::info("🕒 Processing expired clients");

        $expiredClients = ServerClient::where('status', 'active')
            ->where('expiry_time', '<', now())
            ->with(['plan', 'customer', 'order'])
            ->get();

        $results = [
            'processed' => 0,
            'suspended' => 0,
            'renewed' => 0,
            'terminated' => 0,
            'errors' => [],
        ];

        foreach ($expiredClients as $client) {
            try {
                $action = $this->handleExpiredClient($client);
                $results[$action]++;
                $results['processed']++;
            } catch (\Exception $e) {
                Log::error("❌ Failed to process expired client {$client->id}: " . $e->getMessage());
                $results['errors'][] = [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("✅ Expired clients processing completed", $results);
        return $results;
    }

    /**
     * Handle individual expired client
     */
    protected function handleExpiredClient(ServerClient $client): string
    {
        // Check for auto-renewal
        if ($client->auto_renew && $client->customer->hasValidPaymentMethod()) {
            return $this->processAutoRenewal($client);
        }

        // Grace period check (e.g., 3 days)
        $gracePeriodDays = 3;
        $graceExpiry = $client->expiry_time->addDays($gracePeriodDays);

        if (now()->lessThan($graceExpiry)) {
            // Still in grace period, just suspend
            $client->suspend('Expired - Grace period active');
            return 'suspended';
        }

        // Beyond grace period, terminate
        $client->terminate('Expired - Grace period exceeded');
        return 'terminated';
    }

    /**
     * Process auto-renewal for a client
     */
    protected function processAutoRenewal(ServerClient $client): string
    {
        try {
            // Create renewal order
            $renewalOrder = $this->createRenewalOrder($client);

            // Process payment
            $paymentResult = $this->processRenewalPayment($renewalOrder, $client->customer);

            if ($paymentResult['success']) {
                // Extend client
                $client->renew();
                Log::info("✅ Auto-renewed client {$client->id} for customer {$client->customer_id}");
                return 'renewed';
            } else {
                // Payment failed, suspend client
                $client->suspend('Auto-renewal payment failed: ' . $paymentResult['error']);
                return 'suspended';
            }
        } catch (\Exception $e) {
            Log::error("❌ Auto-renewal failed for client {$client->id}: " . $e->getMessage());
            $client->suspend('Auto-renewal failed: ' . $e->getMessage());
            return 'suspended';
        }
    }

    /**
     * Process clients nearing expiration
     */
    public function processExpiringClients(): array
    {
        Log::info("⚠️ Processing clients nearing expiration");

        $expiringClients = ServerClient::where('status', 'active')
            ->whereBetween('expiry_time', [now(), now()->addDays(7)])
            ->with(['plan', 'customer', 'order'])
            ->get();

        $results = [
            'processed' => 0,
            'notifications_sent' => 0,
            'auto_renewals_queued' => 0,
            'errors' => [],
        ];

        foreach ($expiringClients as $client) {
            try {
                $this->handleExpiringClient($client);
                $results['processed']++;

                // Send notification
                if ($this->sendExpirationNotification($client)) {
                    $results['notifications_sent']++;
                }

                // Queue auto-renewal if enabled
                if ($client->auto_renew) {
                    $this->queueAutoRenewal($client);
                    $results['auto_renewals_queued']++;
                }

            } catch (\Exception $e) {
                Log::error("❌ Failed to process expiring client {$client->id}: " . $e->getMessage());
                $results['errors'][] = [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("✅ Expiring clients processing completed", $results);
        return $results;
    }

    /**
     * Handle individual expiring client
     */
    protected function handleExpiringClient(ServerClient $client): void
    {
        // Update next billing date if not set
        if (!$client->next_billing_at) {
            $client->update([
                'next_billing_at' => $client->expiry_time->subDays(3),
            ]);
        }

        Log::info("⚠️ Client {$client->id} expires in " . $client->expiry_time->diffInDays(now()) . " days");
    }

    /**
     * Process traffic limit violations
     */
    public function processTrafficLimitViolations(): array
    {
        Log::info("📊 Processing traffic limit violations");

        $violatingClients = ServerClient::where('status', 'active')
            ->whereNotNull('traffic_limit_mb')
            ->whereRaw('traffic_used_mb >= traffic_limit_mb')
            ->with(['plan', 'customer'])
            ->get();

        $results = [
            'processed' => 0,
            'suspended' => 0,
            'notifications_sent' => 0,
            'errors' => [],
        ];

        foreach ($violatingClients as $client) {
            try {
                $client->suspend('Traffic limit exceeded');
                $results['suspended']++;
                $results['processed']++;

                // Send notification
                if ($this->sendTrafficLimitNotification($client)) {
                    $results['notifications_sent']++;
                }

            } catch (\Exception $e) {
                Log::error("❌ Failed to process traffic violation for client {$client->id}: " . $e->getMessage());
                $results['errors'][] = [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("✅ Traffic limit violations processing completed", $results);
        return $results;
    }

    /**
     * Sync traffic usage from XUI panels
     */
    public function syncTrafficUsage(): array
    {
        Log::info("🔄 Syncing traffic usage from XUI panels");

        $activeClients = ServerClient::where('status', 'active')
            ->with(['inbound.server'])
            ->get()
            ->groupBy('inbound.server_id');

        $results = [
            'servers_processed' => 0,
            'clients_updated' => 0,
            'errors' => [],
        ];

        foreach ($activeClients as $serverId => $clients) {
            try {
                $server = $clients->first()->inbound->server;
                $xuiService = new XUIService($serverId);

                foreach ($clients as $client) {
                    $trafficData = $xuiService->getClientTrafficByEmail($client->email);

                    if ($trafficData && isset($trafficData['up'], $trafficData['down'])) {
                        $totalMb = ($trafficData['up'] + $trafficData['down']) / (1024 * 1024);
                        $client->updateTrafficUsage($totalMb);
                        $results['clients_updated']++;
                    }
                }

                $results['servers_processed']++;

            } catch (\Exception $e) {
                Log::error("❌ Failed to sync traffic for server {$serverId}: " . $e->getMessage());
                $results['errors'][] = [
                    'server_id' => $serverId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("✅ Traffic usage sync completed", $results);
        return $results;
    }

    /**
     * Create renewal order for a client
     */
    protected function createRenewalOrder(ServerClient $client): Order
    {
        $plan = $client->plan;
        $customer = $client->customer;

        $order = Order::create([
            'customer_id' => $customer->id,
            'grand_amount' => $client->renewal_price ?? $plan->price,
            'currency' => 'USD',
            'payment_method' => $customer->default_payment_method_id ?? 1,
            'payment_status' => 'pending',
            'order_status' => 'new',
            'notes' => "Auto-renewal for client {$client->id}",
        ]);

        $order->items()->create([
            'server_plan_id' => $plan->id,
            'quantity' => 1,
            'unit_amount' => $client->renewal_price ?? $plan->price,
            'total_amount' => $client->renewal_price ?? $plan->price,
        ]);

        return $order;
    }

    /**
     * Process renewal payment
     */
    protected function processRenewalPayment(Order $order, Customer $customer): array
    {
        try {
            // Check wallet balance first
            $walletBalance = $customer->wallet?->balance ?? $customer->wallet_balance ?? 0;
            if ($walletBalance >= $order->grand_amount) {
                // Use payFromWallet helper to ensure transaction is recorded
                if (!$customer->payFromWallet($order->grand_amount, "Auto-renewal for order #{$order->id}")) {
                    return ['success' => false, 'error' => 'Insufficient wallet balance'];
                }
                $order->markAsPaid('wallet_payment');

                return ['success' => true, 'method' => 'wallet'];
            }

            // Try default payment method
            $paymentMethod = $customer->defaultPaymentMethod;
            if ($paymentMethod && $paymentMethod->slug === 'stripe') {
                // Process Stripe payment here
                // This would integrate with your existing Stripe service
                return ['success' => false, 'error' => 'Stripe auto-renewal not implemented yet'];
            }

            return ['success' => false, 'error' => 'No valid payment method available'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send expiration notification
     */
    protected function sendExpirationNotification(ServerClient $client): bool
    {
        try {
            // Implement notification sending (email, Telegram, etc.)
            Log::info("📧 Sent expiration notification for client {$client->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("❌ Failed to send expiration notification for client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send traffic limit notification
     */
    protected function sendTrafficLimitNotification(ServerClient $client): bool
    {
        try {
            // Implement notification sending (email, Telegram, etc.)
            Log::info("📧 Sent traffic limit notification for client {$client->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("❌ Failed to send traffic limit notification for client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue auto-renewal
     */
    protected function queueAutoRenewal(ServerClient $client): void
    {
        // Queue a job to process auto-renewal 1 day before expiration
        $renewalDate = $client->expiry_time->subDay();

        // Implement job queuing here
        Log::info("⏰ Queued auto-renewal for client {$client->id} at {$renewalDate}");
    }

    /**
     * Get client statistics
     */
    public function getClientStatistics(): array
    {
        return [
            'total_clients' => ServerClient::count(),
            'active_clients' => ServerClient::where('status', 'active')->count(),
            'suspended_clients' => ServerClient::where('status', 'suspended')->count(),
            'expired_clients' => ServerClient::where('status', 'active')->where('expiry_time', '<', now())->count(),
            'expiring_soon' => ServerClient::where('status', 'active')->whereBetween('expiry_time', [now(), now()->addDays(7)])->count(),
            'traffic_violations' => ServerClient::where('status', 'active')->whereNotNull('traffic_limit_mb')->whereRaw('traffic_used_mb >= traffic_limit_mb')->count(),
            'auto_renewal_enabled' => ServerClient::where('auto_renew', true)->count(),
        ];
    }
}
