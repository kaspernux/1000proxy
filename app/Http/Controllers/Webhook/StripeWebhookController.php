<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;
use App\Http\Controllers\PaymentController;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info('Stripe Webhook Received', $request->all());
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }
        // Delegate to PaymentController for unified processing
        $paymentController = app(PaymentController::class);
        $result = $paymentController->handleWebhook($request, 'stripe');
        return response()->json($result);
    }
}
