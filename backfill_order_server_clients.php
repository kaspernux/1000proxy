<?php

/**
 * Backfill missing OrderServerClient pivot records
 * 
 * This script creates missing pivot table entries for existing ServerClient records
 * that have order_id but no corresponding OrderServerClient record.
 * This fixes the display issue for orders like 8114, 8115, 8116.
 */

echo "=== Backfilling OrderServerClient Records ===\n";

// Find ServerClient records that have order_id but no OrderServerClient pivot record
$serverClientsWithoutPivot = \App\Models\ServerClient::whereNotNull('order_id')
    ->whereDoesntHave('orderServerClients')
    ->with(['order', 'plan'])
    ->get();

echo "Found {$serverClientsWithoutPivot->count()} ServerClient records without OrderServerClient pivot records\n";

$backfilledCount = 0;
$errorCount = 0;

foreach ($serverClientsWithoutPivot as $serverClient) {
    try {
        $order = $serverClient->order;
        if (!$order) {
            echo "Skipping ServerClient {$serverClient->id} - order not found\n";
            continue;
        }

        // Find the order item that matches this server client's plan
        $orderItem = $order->items()
            ->where('server_plan_id', $serverClient->plan_id)
            ->first();

        if (!$orderItem) {
            echo "Skipping ServerClient {$serverClient->id} - no matching order item found for plan {$serverClient->plan_id}\n";
            continue;
        }

        // Create the missing OrderServerClient record
        $orderServerClient = \App\Models\OrderServerClient::create([
            'order_id' => $serverClient->order_id,
            'order_item_id' => $orderItem->id,
            'server_client_id' => $serverClient->id,
            'server_inbound_id' => $serverClient->server_inbound_id,
            'provision_status' => 'completed', // Assume completed since ServerClient exists
            'provision_attempts' => 1,
            'provision_started_at' => $serverClient->created_at,
            'provision_completed_at' => $serverClient->created_at,
            'provision_config' => [
                'backfilled' => true,
                'original_order_id' => $serverClient->order_id,
                'original_plan_id' => $serverClient->plan_id,
            ],
            'provision_log' => [
                'note' => 'Backfilled missing pivot record',
                'server_client_id' => $serverClient->id,
                'backfilled_at' => now()->toISOString(),
            ],
        ]);

        echo "✓ Created OrderServerClient #{$orderServerClient->id} for ServerClient #{$serverClient->id} (Order #{$serverClient->order_id})\n";
        $backfilledCount++;
        
    } catch (\Throwable $e) {
        echo "✗ Error processing ServerClient {$serverClient->id}: {$e->getMessage()}\n";
        $errorCount++;
    }
}

echo "\n=== Backfill Summary ===\n";
echo "Successfully backfilled: {$backfilledCount} records\n";
echo "Errors encountered: {$errorCount} records\n";

// Specifically check orders 8114, 8115, 8116
echo "\n=== Checking Specific Orders ===\n";
$targetOrders = [8114, 8115, 8116];
foreach ($targetOrders as $orderId) {
    $order = \App\Models\Order::find($orderId);
    if ($order) {
        $pivotCount = \App\Models\OrderServerClient::where('order_id', $orderId)->count();
        $clientCount = \App\Models\ServerClient::where('order_id', $orderId)->count();
        echo "Order #{$orderId}: {$clientCount} ServerClients, {$pivotCount} OrderServerClient records\n";
        
        foreach ($order->items as $item) {
            $itemClientCount = $item->serverClients()->count();
            echo "  Item #{$item->id}: {$itemClientCount} linked clients\n";
        }
    } else {
        echo "Order #{$orderId}: Not found\n";
    }
}

echo "\n=== Backfill Complete ===\n";