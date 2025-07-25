<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;

class NowPaymentsWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info('NowPayments Webhook Received', $request->all());

        $paymentStatus = $request->input('payment_status');
        $orderId = $request->input('order_id');

        if (!$orderId || !$paymentStatus) {
            Log::error('NowPayments webhook missing required fields.');
            return response('Missing fields', 422);
        }

        $order = Order::find($orderId);

        if (!$order) {
            Log::error('NowPayments webhook Order not found.', ['order_id' => $orderId]);
            return response('Order not found', 404);
        }

        if (in_array($paymentStatus, ['finished', 'confirmed']) && $order) {
            \App\Services\OrderService::payAndProcessClients($order);

            Log::info('✅ NowPayments: Order marked paid and client creation job dispatched.', ['order_id' => $order->id]);
        } else {
            Log::info('NowPayments webhook ignored.', ['order_id' => $orderId, 'payment_status' => $paymentStatus]);
        }

        return response('Webhook Handled', 200);
    }
}
