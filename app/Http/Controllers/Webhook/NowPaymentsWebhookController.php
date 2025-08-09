<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PaymentController;

class NowPaymentsWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info('NowPayments Webhook Received', $request->all());
        // Delegate to PaymentController for unified processing
        $paymentController = app(PaymentController::class);
        $result = $paymentController->handleWebhook($request, 'nowpayments');
        return response()->json($result);
    }
}
