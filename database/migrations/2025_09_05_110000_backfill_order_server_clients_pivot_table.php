<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill missing OrderServerClient records for existing ServerClients
        // This ensures existing orders will show configuration clients after the relationship fix
        
        $this->command->info('Backfilling missing OrderServerClient pivot records...');
        
        // Find ServerClient records that have order_id but no OrderServerClient pivot record
        $serverClients = DB::table('server_clients as sc')
            ->leftJoin('order_server_clients as osc', 'sc.id', '=', 'osc.server_client_id')
            ->whereNotNull('sc.order_id')
            ->whereNull('osc.id')
            ->select([
                'sc.id as server_client_id',
                'sc.order_id',
                'sc.plan_id',
                'sc.server_inbound_id',
                'sc.created_at',
                'sc.updated_at'
            ])
            ->get();

        $this->command->info("Found {$serverClients->count()} ServerClient records without OrderServerClient pivot records");

        $backfilledCount = 0;
        $errorCount = 0;

        foreach ($serverClients as $serverClient) {
            try {
                // Find the order item that matches this server client's plan
                $orderItem = DB::table('order_items')
                    ->where('order_id', $serverClient->order_id)
                    ->where('server_plan_id', $serverClient->plan_id)
                    ->first();

                if (!$orderItem) {
                    $this->command->warn("Skipping ServerClient {$serverClient->server_client_id} - no matching order item found");
                    continue;
                }

                // Create the missing OrderServerClient record
                DB::table('order_server_clients')->insert([
                    'order_id' => $serverClient->order_id,
                    'order_item_id' => $orderItem->id,
                    'server_client_id' => $serverClient->server_client_id,
                    'server_inbound_id' => $serverClient->server_inbound_id,
                    'provision_status' => 'completed', // Assume completed since ServerClient exists
                    'provision_attempts' => 1,
                    'provision_started_at' => $serverClient->created_at,
                    'provision_completed_at' => $serverClient->created_at,
                    'provision_config' => json_encode([
                        'backfilled' => true,
                        'original_order_id' => $serverClient->order_id,
                        'original_plan_id' => $serverClient->plan_id,
                    ]),
                    'provision_log' => json_encode([
                        'note' => 'Backfilled missing pivot record during migration',
                        'server_client_id' => $serverClient->server_client_id,
                        'backfilled_at' => now()->toISOString(),
                    ]),
                    'created_at' => $serverClient->created_at,
                    'updated_at' => $serverClient->updated_at,
                ]);

                $backfilledCount++;
                
            } catch (\Throwable $e) {
                $this->command->error("Error processing ServerClient {$serverClient->server_client_id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->command->info("Backfill complete: {$backfilledCount} records created, {$errorCount} errors");

        // Specifically check and report on the problematic orders mentioned in the issue
        $targetOrders = [8114, 8115, 8116];
        foreach ($targetOrders as $orderId) {
            $pivotCount = DB::table('order_server_clients')->where('order_id', $orderId)->count();
            $clientCount = DB::table('server_clients')->where('order_id', $orderId)->count();
            $this->command->info("Order #{$orderId}: {$clientCount} ServerClients, {$pivotCount} OrderServerClient records");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove backfilled records (identified by backfilled flag in provision_config)
        DB::table('order_server_clients')
            ->whereRaw("JSON_EXTRACT(provision_config, '$.backfilled') = true")
            ->delete();
    }
};