<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;

class NowPaymentsWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('NowPayments Webhook Received', $request->all());

        $paymentStatus = $request->input('payment_status');
        $orderId = $request->input('order_id');

        if (!$orderId || !$paymentStatus) {
            return response()->json(['error' => 'Missing required fields'], 422);
        }

        $order = Order::find($orderId);

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($paymentStatus === 'finished' || $paymentStatus === 'confirmed') {
            $order->markAsPaid($order->payment_invoice_url);
            dispatch(new ProcessXuiOrder($order->load('items.serverPlan')));        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    public function __invoke(Request $request)
    {
        Log::info('NowPayments Webhook Received', $request->all());

        $paymentStatus = $request->input('payment_status');
        $orderId = $request->input('order_id'); // make sure this is sent by you in metadata

        if (!$orderId || !in_array($paymentStatus, ['finished', 'confirmed'])) {
            return response()->json(['status' => 'ignored'], 400);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['status' => 'order not found'], 404);
        }

        if ($order->payment_status !== 'paid') {
            $order->markAsPaid($order->payment_invoice_url);
            dispatch(new ProcessXuiOrder($order->load('items.serverPlan')));        }

        return response()->json(['status' => 'ok']);
    }
}
