<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class ProvisionOrderDebug extends Command
{
    protected $signature = 'debug:provision-order {order_id}';
    protected $description = 'Run full provisioning for a single order (debug helper)';

    public function handle(): int
    {
        $id = $this->argument('order_id');
        $order = Order::find($id);
        if (!$order) {
            $this->error("Order not found: {$id}");
            return 1;
        }
        $this->info("Provisioning order {$id}");
        $svc = app()->make(\App\Services\ClientProvisioningService::class);
        $results = $svc->provisionOrder($order);
        $this->info(json_encode($results, JSON_PRETTY_PRINT));
        return 0;
    }
}
