<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderServerClient;
use App\Models\ServerClient;
use Illuminate\Console\Command;

class CheckOrderClientStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:check-clients {order_ids* : The order IDs to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the client provisioning status for specific orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderIds = $this->argument('order_ids');
        
        $this->info('Checking client status for orders: ' . implode(', ', $orderIds));
        $this->newLine();

        foreach ($orderIds as $orderId) {
            $this->checkOrder($orderId);
            $this->newLine();
        }
    }

    private function checkOrder($orderId)
    {
        $this->info("=== Order #{$orderId} ===");
        
        $order = Order::find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }
        
        $this->line("Status: {$order->status}");
        $this->line("Payment Status: {$order->payment_status}");
        $this->line("Items Count: {$order->items->count()}");
        
        // Check overall order client relationships
        $orderClients = $order->clients;
        $orderServerClients = $order->orderServerClients;
        $this->line("Order Clients (via relationship): {$orderClients->count()}");
        $this->line("Order Server Clients (pivot): {$orderServerClients->count()}");
        
        foreach ($order->items as $index => $item) {
            $this->newLine();
            $this->line("  --- Item #{$item->id} ---");
            $this->line("    Server Plan ID: {$item->server_plan_id}");
            $this->line("    Quantity: {$item->quantity}");
            
            // Check direct ServerClient query (legacy approach)
            $directClients = ServerClient::where('order_id', $order->id)
                ->where('plan_id', $item->server_plan_id)
                ->get();
            $this->line("    Direct ServerClient query: {$directClients->count()} found");
            
            // Check via new relationship
            try {
                $relationshipClients = $item->serverClients;
                $this->line("    Via serverClients() relationship: {$relationshipClients->count()} found");
                
                foreach ($relationshipClients as $client) {
                    $this->line("      Client ID: {$client->id}");
                    $this->line("        client_link: " . ($client->client_link ? 'Present' : 'Missing'));
                    $this->line("        remote_sub_link: " . ($client->remote_sub_link ? 'Present' : 'Missing'));
                    $this->line("        remote_json_link: " . ($client->remote_json_link ? 'Present' : 'Missing'));
                }
            } catch (\Throwable $e) {
                $this->error("    Relationship error: {$e->getMessage()}");
            }
            
            // Check OrderServerClient pivot records
            $pivotRecords = OrderServerClient::where('order_id', $order->id)
                ->where('order_item_id', $item->id)
                ->get();
            $this->line("    OrderServerClient pivot records: {$pivotRecords->count()}");
            
            foreach ($pivotRecords as $pivot) {
                $this->line("      - Client ID: {$pivot->server_client_id}, Status: {$pivot->provision_status}");
            }
            
            // Check server_client accessor (what the view uses)
            try {
                $serverClient = $item->server_client;
                if ($serverClient) {
                    $this->line("    server_client accessor: ✓ Found client {$serverClient->id}");
                    $configStatus = [];
                    if ($serverClient->client_link) $configStatus[] = 'client_link';
                    if ($serverClient->remote_sub_link) $configStatus[] = 'sub_link';
                    if ($serverClient->remote_json_link) $configStatus[] = 'json_link';
                    
                    if (empty($configStatus)) {
                        $this->error("      ✗ No configuration links available");
                    } else {
                        $this->line("      ✓ Available configs: " . implode(', ', $configStatus));
                    }
                } else {
                    $this->error("    server_client accessor: ✗ Returns NULL");
                }
            } catch (\Throwable $e) {
                $this->error("    server_client accessor error: {$e->getMessage()}");
            }
        }
    }
}