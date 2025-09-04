<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PaymentController;
use Symfony\Component\HttpFoundation\Response;

class NowPaymentsWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // Verify HMAC signature
        $ipnSecret = env('NOWPAYMENTS_IPN_SECRET') ?: env('NOWPAYMENTS_WEBHOOK_SECRET');
        $signature = $request->header('x-nowpayments-sig');
        $payload = $request->all();
        // Ensure payout_currency key exists to prevent undefined index notices downstream
        if (!isset($payload['payout_currency'])) {
            $payload['payout_currency'] = $payload['outcome_currency']
                ?? ($payload['pay_currency'] ?? ($payload['price_currency'] ?? null));
        }
        $valid = false;
        if ($ipnSecret && $signature) {
            $sorted = $this->recursiveKeySort($payload);
            $encoded = json_encode($sorted, JSON_UNESCAPED_SLASHES);
            $hmac = hash_hmac('sha512', $encoded, trim($ipnSecret));
            if (hash_equals($hmac, $signature)) {
                $valid = true;
            }
        }
        if (!$valid) {
            Log::warning('NowPayments Webhook signature invalid', [
                'received' => $signature,
                'calculated' => isset($hmac) ? $hmac : null,
            ]);
            return response()->json(['success' => false, 'error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
        }
    Log::info('NowPayments Webhook Received (verified)', $payload);
    // Mark request as verified so unified handler doesn't re-check with a different algorithm
    $request->attributes->set('nowpayments_verified', true);
    // Delegate to PaymentController for unified processing (updates order / wallet transaction)
    $paymentController = app(PaymentController::class);
    $result = $paymentController->handleWebhook($request, 'nowpayments');
    return response()->json($result);
    }

    private function recursiveKeySort(array $array): array
    {
        ksort($array);
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = $this->recursiveKeySort($v);
            }
        }
        return $array;
    }
}
