<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class RetryProvisionForOrders extends Command
{
    protected $signature = 'debug:retry-provision {order_ids*}';
    protected $description = 'Retry failed provisioning for given orders (debug helper)';

    public function handle(): int
    {
        $ids = $this->argument('order_ids');
        foreach ($ids as $id) {
            $order = Order::find($id);
            if (!$order) {
                $this->error("Order not found: {$id}");
                continue;
            }
            $this->info("Retrying provisions for order {$id}");
            $svc = app()->make(\App\Services\ClientProvisioningService::class);
            $results = $svc->retryFailedProvisions($order);
            $this->info(json_encode($results));
        }
        return 0;
    }
}
