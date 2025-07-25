<?php

namespace App\Services;

use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderService
{
    /**
     * Mark an order as paid and create XUI clients.
     *
     * @param Order $order
     * @return bool
     */
    public static function payAndProcessClients(Order $order): bool
    {
        try {
            if ($order->payment_status === 'paid') {
                Log::info('Order already marked as paid, skipping.', ['order_id' => $order->id]);
                return true;
            }

            // ✅ Mark as paid
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'completed',
            ]);

            Log::info('✅ Order marked as paid.', ['order_id' => $order->id]);

            // ✅ Dispatch job to create clients from order
            dispatch(new ProcessXuiOrder($order->load('items.serverPlan')));

            Log::info('✅ Client creation job dispatched for Order.', ['order_id' => $order->id]);

            return true;
        } catch (Throwable $e) {
            Log::error('❌ Failed to mark order as paid and dispatch ProcessXuiOrder.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
