<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderServerClient;
use App\Services\ClientProvisioningService;
use App\Jobs\ProcessXuiOrder;

class ReprovisionOrders extends Command
{
    protected $signature = 'orders:reprovision {orderIds*} {--force : Force reprovisioning even if already provisioned}';
    protected $description = 'Reprovision specific orders that may have failed';

    public function handle()
    {
        $orderIds = $this->argument('orderIds');
        $force = $this->option('force');
        
        $this->info("Reprovisioning orders: " . implode(', ', $orderIds));
        
        if ($force) {
            $this->warn("Force mode enabled - will reprovision even if already provisioned");
        }
        
        $this->newLine();

        foreach ($orderIds as $orderId) {
            $this->reprovisionOrder($orderId, $force);
            $this->newLine();
        }
    }

    private function reprovisionOrder($orderId, $force = false)
    {
        $this->info("=== REPROVISIONING ORDER #{$orderId} ===");
        
        $order = Order::with(['items.serverPlan', 'customer'])->find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }

        // Check if order is eligible for provisioning
        if ($order->payment_status !== 'paid') {
            $this->error("Order payment status is '{$order->payment_status}' - must be 'paid' to provision");
            return;
        }

        // Check if already provisioned (unless force)
        if (!$force) {
            $existingClients = $order->getAllClients();
            if ($existingClients->isNotEmpty()) {
                $this->warn("Order already has {$existingClients->count()} provisioned clients");
                $this->line("Use --force to reprovision anyway");
                return;
            }
        }

        $this->line("Customer: " . (($order->customer && $order->customer->name) ? $order->customer->name : 'Unknown') . " (ID: {$order->customer_id})");
        $this->line("Items: {$order->items->count()}");

        // Clear existing failed provisions if forcing
        if ($force) {
            $this->line("Clearing existing OrderServerClient records...");
            OrderServerClient::where('order_id', $orderId)->delete();
        }

        try {
            $this->line("Starting provisioning...");
            
            $provisioningService = app(ClientProvisioningService::class);
            $results = $provisioningService->provisionOrder($order);
            
            $this->info("✅ Provisioning completed");
            
            $totalSuccess = 0;
            $totalFailed = 0;
            
            foreach ($results as $itemId => $result) {
                $success = $result['success'] ?? false;
                $requested = $result['quantity_requested'] ?? 0;
                $provisioned = $result['quantity_provisioned'] ?? 0;
                
                if ($success) {
                    $totalSuccess += $provisioned;
                    $this->line("  ✅ Item #{$itemId}: {$provisioned}/{$requested} clients provisioned");
                } else {
                    $totalFailed += ($requested - $provisioned);
                    $this->line("  ❌ Item #{$itemId}: Failed - " . ($result['error'] ?? 'Unknown error'));
                }
            }
            
            $this->newLine();
            $this->info("Summary: {$totalSuccess} clients provisioned, {$totalFailed} failed");
            
            // Refresh order and check status
            $order->refresh();
            $this->line("Order status: {$order->status}");
            
            // Show available configurations
            $configurations = $order->getClientConfigurations();
            $this->line("Available configurations: " . count($configurations));
            
            if (!empty($configurations)) {
                $this->info("🎉 Clients are now available on the product detail page");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Provisioning failed: " . $e->getMessage());
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
        }
    }
}