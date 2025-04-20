<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Stripe Webhook Received', $request->all());

        $payload = $request->all();
        $eventType = $payload['type'] ?? null;

        if ($eventType === 'checkout.session.completed') {
            $session = $payload['data']['object'] ?? [];
            $orderId = $session['metadata']['order_id'] ?? null;

            if (!$orderId) {
                return response()->json(['error' => 'Missing order ID'], 400);
            }

            $order = Order::find($orderId);

            if (! $order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            $order->markAsPaid($order->payment_invoice_url);
            dispatch(new ProcessXuiOrder($order->load('items.serverPlan')));        }

        return response()->json(['message' => 'Webhook handled'], 200);
    }

    public function __invoke(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if (!isset($session->metadata->order_id)) {
                return response()->json(['error' => 'No order_id in metadata'], 400);
            }

            $order = Order::find($session->metadata->order_id);

            if ($order && $order->payment_status !== 'paid') {
                $order->markAsPaid($order->payment_invoice_url);
                dispatch(new ProcessXuiOrder($order->load('items.serverPlan')));            }
        }

        return response()->json(['status' => 'ok']);
    }
}

