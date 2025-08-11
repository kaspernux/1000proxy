<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPaidMail;

class DispatchProvisioningOnOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order->fresh(['customer','items.serverPlan']);
        if (!$order) { return; }
        $cacheKey = 'order_provisioning_dispatched_' . $order->id;
        if (!Cache::add($cacheKey, true, now()->addMinutes(10))) {
            return; // already dispatched recently
        }
        Log::info('📨 Dispatching provisioning job from OrderPaid listener', ['order_id' => $order->id]);
        ProcessXuiOrder::dispatchWithDependencies($order);
        try {
            if ($order->customer && filter_var($order->customer->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($order->customer->email)->queue(new OrderPaidMail($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed sending OrderPaidMail', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
