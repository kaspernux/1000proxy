<?php

namespace App\Listeners;

use App\Events\OrderProvisioned;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderProvisionedMail;

class SendOrderProvisionedNotification
{
    public function handle(OrderProvisioned $event): void
    {
        $order = $event->order->fresh('customer');
        if (!$order || !$order->customer) { return; }
        try {
            if (filter_var($order->customer->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($order->customer->email)->queue(new OrderProvisionedMail($order, $event->results));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed sending OrderProvisionedMail', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
