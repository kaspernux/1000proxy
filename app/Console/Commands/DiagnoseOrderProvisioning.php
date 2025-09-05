<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderServerClient;
use App\Models\ServerClient;
use App\Services\ClientProvisioningService;
use App\Jobs\ProcessXuiOrder;

class DiagnoseOrderProvisioning extends Command
{
    protected $signature = 'orders:diagnose {orderIds*}';
    protected $description = 'Diagnose provisioning status for specific orders';

    public function handle()
    {
        $orderIds = $this->argument('orderIds');
        
        $this->info("Diagnosing orders: " . implode(', ', $orderIds));
        $this->newLine();

        foreach ($orderIds as $orderId) {
            $this->diagnoseOrder($orderId);
            $this->newLine();
        }
    }

    private function diagnoseOrder($orderId)
    {
        $this->info("=== ORDER #{$orderId} ===");
        
        $order = Order::with(['items', 'customer'])->find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }

        // Basic order info
        $this->line("Customer: " . (($order->customer && $order->customer->name) ? $order->customer->name : 'Unknown') . " (ID: {$order->customer_id})");
        $this->line("Status: {$order->status}");
        $this->line("Payment Status: {$order->payment_status}");
        $this->line("Created: {$order->created_at}");
        $this->line("Items: {$order->items->count()}");

        // Check for OrderServerClient records
        $orderClients = OrderServerClient::where('order_id', $orderId)->get();
        $this->line("OrderServerClient records: {$orderClients->count()}");
        
        foreach ($orderClients as $client) {
            $this->line("  - Status: {$client->provision_status}, Attempts: {$client->provision_attempts}");
            if ($client->provision_error) {
                $this->line("    Error: {$client->provision_error}");
            }
        }

        // Check for ServerClient records
        $serverClients = ServerClient::where('order_id', $orderId)->get();
        $this->line("ServerClient records: {$serverClients->count()}");
        
        foreach ($serverClients as $client) {
            $this->line("  - ID: {$client->id}, Status: {$client->status}, Email: {$client->email}");
            $this->line("    Plan ID: {$client->plan_id}, Customer ID: {$client->customer_id}");
            $this->line("    Client Link: " . (empty($client->client_link) ? 'MISSING' : 'Present'));
        }

        // Check provisioning status
        $configurations = $order->getClientConfigurations();
        $this->line("Available configurations: " . count($configurations));

        if (empty($configurations)) {
            $this->warn("❌ No client configurations found for this order");
            
            // Check if order needs provisioning
            if ($order->payment_status === 'paid' && $orderClients->isEmpty()) {
                $this->info("💡 Order appears to need provisioning. Running provisioning service...");
                
                try {
                    $provisioningService = app(ClientProvisioningService::class);
                    $results = $provisioningService->provisionOrder($order);
                    $this->info("✅ Provisioning completed");
                    
                    foreach ($results as $itemId => $result) {
                        $this->line("  Item #{$itemId}: " . ($result['success'] ?? false ? 'Success' : 'Failed'));
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Provisioning failed: " . $e->getMessage());
                }
            } elseif ($order->payment_status !== 'paid') {
                $this->warn("⚠️  Order payment status is not 'paid' - provisioning skipped");
            }
        } else {
            $this->info("✅ Client configurations are available");
        }

        // Check if failed provisions exist
        if ($order->hasFailedProvisions()) {
            $this->warn("⚠️  Order has failed provisions");
        }
    }
}