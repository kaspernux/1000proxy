<?php

/**
 * Debug script to investigate orders 8114, 8115, and 8116
 * This script checks the state of these orders and their client relationships
 */

// This would be run with: php artisan tinker --execute="include 'debug_orders.php'"

echo "=== Debugging Orders 8114, 8115, 8116 ===\n";

$orderIds = [8114, 8115, 8116];

foreach ($orderIds as $orderId) {
    echo "\n--- Order #{$orderId} ---\n";
    
    try {
        $order = \App\Models\Order::find($orderId);
        if (!$order) {
            echo "Order #{$orderId} not found\n";
            continue;
        }
        
        echo "Status: {$order->status}\n";
        echo "Payment Status: {$order->payment_status}\n";
        echo "Items Count: {$order->items->count()}\n";
        
        foreach ($order->items as $index => $item) {
            echo "\n  Item #{$item->id} (Index {$index}):\n";
            echo "    Server Plan ID: {$item->server_plan_id}\n";
            echo "    Quantity: {$item->quantity}\n";
            
            // Check direct ServerClient query
            $directClients = \App\Models\ServerClient::where('order_id', $order->id)
                ->where('plan_id', $item->server_plan_id)
                ->get();
            echo "    Direct ServerClient query count: {$directClients->count()}\n";
            
            // Check via relationship (before fix)
            try {
                $relationshipClients = $item->serverClients;
                echo "    Relationship ServerClients count: {$relationshipClients->count()}\n";
            } catch (\Throwable $e) {
                echo "    Relationship error: {$e->getMessage()}\n";
            }
            
            // Check OrderServerClient pivot records
            $pivotRecords = \App\Models\OrderServerClient::where('order_id', $order->id)
                ->where('order_item_id', $item->id)
                ->get();
            echo "    OrderServerClient pivot records: {$pivotRecords->count()}\n";
            
            foreach ($pivotRecords as $pivot) {
                echo "      - Client ID: {$pivot->server_client_id}, Status: {$pivot->provision_status}\n";
            }
            
            // Check server_client accessor
            try {
                $serverClient = $item->server_client;
                if ($serverClient) {
                    echo "    server_client accessor: Found client {$serverClient->id}\n";
                    echo "      client_link: " . ($serverClient->client_link ? 'Yes' : 'No') . "\n";
                    echo "      remote_sub_link: " . ($serverClient->remote_sub_link ? 'Yes' : 'No') . "\n";
                    echo "      remote_json_link: " . ($serverClient->remote_json_link ? 'Yes' : 'No') . "\n";
                } else {
                    echo "    server_client accessor: NULL\n";
                }
            } catch (\Throwable $e) {
                echo "    server_client accessor error: {$e->getMessage()}\n";
            }
        }
        
    } catch (\Throwable $e) {
        echo "Error processing order #{$orderId}: {$e->getMessage()}\n";
    }
}

echo "\n=== Debug Complete ===\n";