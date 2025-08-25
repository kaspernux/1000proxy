<?php
// scripts/dev/test_nowpayments_api.php
// Purpose: Initiate a NowPayments order via the public API route and print the persisted invoice row.

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $email = 'demo@1000proxy.io';
    $customer = App\Models\Customer::where('email', $email)->first();
    if (!$customer) {
        throw new RuntimeException('Customer not found: ' . $email);
    }
    // Use latest unpaid order or create a new one with a real plan
    $order = App\Models\Order::where('customer_id', $customer->id)
        ->where('payment_status', '!=', 'paid')
        ->latest('id')
        ->first();
    if (!$order) {
        $plan = App\Models\ServerPlan::where('slug', 'vmess-proxy-dedic-blk')->first() ?: App\Models\ServerPlan::first();
        if (!$plan) {
            throw new RuntimeException('No ServerPlan found to create test order');
        }
        $order = new App\Models\Order();
        $order->customer_id = $customer->id;
        $order->status = 'new';
        $order->payment_status = 'pending';
        $order->currency = 'USD';
        $order->total_amount = $plan->price ?? 12.00;
        $order->save();
        $order->items()->create([
            'server_plan_id' => $plan->id,
            'name' => $plan->name,
            'price' => $plan->price ?? 12.00,
            'quantity' => 1,
        ]);
    }

    // Create a short-lived API token and call the API route internally via HTTP Kernel
    $token = $customer->createToken('np-api-test')->plainTextToken;
    $payload = [
        'amount' => (float)($order->total_amount ?? 12.0),
        'currency' => 'USD',
        'gateway' => 'nowpayments',
        'order_id' => $order->id,
        'crypto_currency' => 'XMR',
    ];

    $json = json_encode($payload);
    // Build a Symfony request to /api/payment/create with Bearer token
    $symReq = Illuminate\Http\Request::create(
        '/api/payment/create',
        'POST',
        [],
        [],
        [],
        [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        $json
    );

    /** @var \Illuminate\Contracts\Http\Kernel $httpKernel */
    $httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $response = $httpKernel->handle($symReq);
    $httpCode = $response->getStatusCode();
    $resp = $response->getContent();
    $respJson = json_decode($resp ?: 'null', true);
    // Terminate kernel for proper middleware termination hooks
    $httpKernel->terminate($symReq, $response);

    // Revoke the token regardless (ignore if model changes)
    try { $customer->tokens()->where('name', 'np-api-test')->delete(); } catch (Throwable $e) {}

    // Obtain invoice id either from response or DB
    $invoiceId = $respJson['data']['invoice_id'] ?? null;
    if (!$invoiceId) {
        $invoiceId = optional(App\Models\Invoice::where('order_id', $order->id)->latest('id')->first())->id;
    }

    $invoice = $invoiceId ? App\Models\Invoice::find($invoiceId) : null;

    $out = [
        'ok' => (bool)$invoice,
        'http_code' => $httpCode,
    'api_error' => null,
        'order_id' => $order->id,
        'api_response' => $respJson,
        'invoice' => $invoice ? [
            'id' => $invoice->id,
            'order_id' => $invoice->order_id,
            'payment_id' => $invoice->payment_id,
            'invoice_url' => $invoice->invoice_url,
            'price_amount' => $invoice->price_amount,
            'price_currency' => $invoice->price_currency,
            'pay_currency' => $invoice->pay_currency,
            'pay_amount' => $invoice->pay_amount,
            'payment_status' => $invoice->payment_status,
            'ipn_callback_url' => $invoice->ipn_callback_url,
            'success_url' => $invoice->success_url,
            'cancel_url' => $invoice->cancel_url,
        ] : null,
    ];

    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($invoice ? 0 : 2);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'trace' => app()->environment('production') ? null : explode("\n", $e->getTraceAsString()),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
