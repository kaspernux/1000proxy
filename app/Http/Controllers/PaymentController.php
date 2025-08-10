<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Http\Requests\CreatePaymentRequest;
use App\Http\Requests\EstimatePriceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use PrevailExcel\Nowpayments\Facades\Nowpayments;


class PaymentController extends Controller
{
    protected $middleware = [
        'auth',
        ['throttle:60,1', ['only' => ['createCryptoPayment', 'createInvoice']]],
        ['throttle:30,1', ['only' => ['getEstimatePrice']]],
    ];

    /**
     * Unified payment creation endpoint
     */
    public function createPayment(Request $request): JsonResponse
    {
        // Validate request: amount, currency, gateway, order_id, etc.
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'gateway' => 'required|string',
            'order_id' => 'nullable|integer',
        ]);

    try {
            $gateway = strtolower($validated['gateway']);
            $amount = $validated['amount'];
            $currency = strtolower($validated['currency']);
            $orderId = $validated['order_id'] ?? null;
            $user = Auth::user();
            // Strict separation: wallet top-ups must use /payment/topup endpoint
            // If client mistakenly sends wallet_topup flag, reject (defensive)
            if ($request->boolean('wallet_topup')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Use /api/payment/topup for wallet top-ups.'
                ], 422);
            }

            // Order payment logic
            if ($orderId) {
                $order = Order::findOrFail($orderId);
                if ($order->user_id !== $user->id) {
                    abort(403, 'Unauthorized access to order.');
                }
                if ($order->payment_status === 'paid') {
                    return response()->json(['error' => 'Order already paid'], 422);
                }
                // Create invoice if not exists
                if (!$order->invoice) {
                    $invoice = Invoice::create([
                        'order_id' => $order->id,
                        'amount' => $amount,
                        'currency' => $currency,
                        'status' => 'pending',
                    ]);
                } else {
                    $invoice = $order->invoice;
                }
                // Call gateway for payment URL
                switch ($gateway) {
                    case 'stripe':
                        $stripeService = app(\App\Services\PaymentGateways\StripePaymentService::class);
                        $result = $stripeService->createPayment([
                            'amount' => $amount,
                            'currency' => $currency,
                            'metadata' => [
                                'order_id' => $order->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => $user->id
                            ]
                        ]);
                        break;
                    case 'paypal':
                        $paypalService = app(\App\Services\PaymentGateways\PayPalPaymentService::class);
                        $result = $paypalService->createPayment([
                            'amount' => $amount,
                            'currency' => $currency,
                            'metadata' => [
                                'order_id' => $order->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => $user->id
                            ]
                        ]);
                        break;
                    case 'mir':
                        $mirService = app(\App\Services\PaymentGateways\MirPaymentService::class);
                        $result = $mirService->createPayment([
                            'amount' => $amount,
                            'currency' => $currency,
                            'metadata' => [
                                'order_id' => $order->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => $user->id
                            ]
                        ]);
                        break;
                    case 'nowpayments':
                        $nowService = app(\App\Services\PaymentGateways\NowPaymentsService::class);
                        $result = $nowService->createPayment([
                            'amount' => $amount,
                            'currency' => $currency,
                            // Critical: ensure we pass the actual numeric order id so webhook can locate the order.
                            // Previously omitted => service generated synthetic WTU-* id, causing webhook order lookup to fail.
                            'order_id' => $order->id,
                            'description' => 'Order #'.$order->id.' Payment',
                            // Optionally allow client to choose crypto currency; fallback to request('crypto_currency') or null
                            'crypto_currency' => $request->input('crypto_currency'),
                            'metadata' => [
                                'order_id' => $order->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => $user->id
                            ]
                        ]);
                        // Safety check: if gateway responded with different order_id, log it for diagnostics
                        if (($result['data']['payment_id'] ?? false) && (($result['data']['order_id'] ?? $order->id) != $order->id)) {
                            \Log::warning('NowPayments order_id mismatch after createPayment', [
                                'expected_order_id' => $order->id,
                                'gateway_order_id' => $result['data']['order_id'] ?? null,
                                'invoice_id' => $invoice->id,
                            ]);
                        }
                        break;
                    case 'wallet':
                        // Direct wallet payment
                        $wallet = $user->wallet ?? $user->getWallet();
                        if ($wallet->balance < $amount) {
                            return response()->json(['success' => false, 'error' => 'Insufficient wallet balance'], 422);
                        }
                        $wallet->decrement('balance', $amount);
                        $wallet->transactions()->create([
                            'type' => 'debit',
                            'amount' => -$amount,
                            'status' => 'completed',
                            'payment_method' => 'wallet',
                            'currency' => $currency,
                            'reference' => 'Order_' . $order->id,
                        ]);
                        $order->update(['payment_status' => 'paid']);
                        $invoice->update(['status' => 'paid']);
                        // Dispatch order processing job
                        if ($order->payment_status === 'paid') {
                            \App\Jobs\ProcessXuiOrder::dispatch($order);
                        }
                        $result = ['success' => true, 'message' => 'Order paid with wallet'];
                        break;
                    default:
                        return response()->json(['success' => false, 'error' => 'Unsupported gateway for order payment'], 400);
                }
                return response()->json([
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'order_id' => $order->id,
                        'gateway' => $gateway,
                        'payment' => $result['data'] ?? $result
                    ]
                ]);
            }

            // Standalone gateway payment (no wallet/order)
            switch ($gateway) {
                case 'stripe':
                    $stripeService = app(\App\Services\PaymentGateways\StripePaymentService::class);
                    $result = $stripeService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'metadata' => [
                            'user_id' => $user->id
                        ]
                    ]);
                    break;
                case 'paypal':
                    $paypalService = app(\App\Services\PaymentGateways\PayPalPaymentService::class);
                    $result = $paypalService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'metadata' => [
                            'user_id' => $user->id
                        ]
                    ]);
                    break;
                case 'mir':
                    $mirService = app(\App\Services\PaymentGateways\MirPaymentService::class);
                    $result = $mirService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'metadata' => [
                            'user_id' => $user->id
                        ]
                    ]);
                    break;
                case 'nowpayments':
                    $nowService = app(\App\Services\PaymentGateways\NowPaymentsService::class);
                    $result = $nowService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'description' => 'Standalone Payment',
                        'crypto_currency' => $request->input('crypto_currency'),
                        'metadata' => [
                            'user_id' => $user->id
                        ]
                    ]);
                    break;
                case 'wallet':
                    $wallet = $user->wallet ?? $user->getWallet();
                    if ($wallet->balance < $amount) {
                        return response()->json(['success' => false, 'error' => 'Insufficient wallet balance'], 422);
                    }
                    $wallet->decrement('balance', $amount);
                    $wallet->transactions()->create([
                        'type' => 'debit',
                        'amount' => -$amount,
                        'status' => 'completed',
                        'payment_method' => 'wallet',
                        'currency' => $currency,
                        'reference' => 'Standalone_' . strtoupper(uniqid()),
                    ]);
                    $result = ['success' => true, 'message' => 'Standalone payment with wallet'];
                    break;
                default:
                    return response()->json(['success' => false, 'error' => 'Unsupported payment gateway'], 400);
            }
            return response()->json([
                'success' => true,
                'error' => null,
                'data' => [
                    'gateway' => $gateway,
                    'payment' => $result['data'] ?? $result
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Unified payment creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Payment creation failed. Please try again.',
                'data' => []
            ], 500);
        }
    }
    /**
     * Unified wallet top-up endpoint
     */
    public function topUpWallet(Request $request): JsonResponse
    {
        // Validate request: amount, currency, gateway
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'gateway' => 'required|string',
        ]);

        try {
            $user = Auth::guard('customer')->user() ?? Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
            }
            $wallet = $user->wallet ?? $user->getWallet();
            $amount = $validated['amount'];
            $currency = strtolower($validated['currency']);
            $gateway = strtolower($validated['gateway']);
            $transaction = $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => $gateway,
                'currency' => $currency,
                'reference' => 'WalletTopup_' . strtoupper(uniqid()),
            ]);
            // Call gateway for payment URL
            switch ($gateway) {
                case 'stripe':
                    $stripeService = app(\App\Services\PaymentGateways\StripePaymentService::class);
                    $result = $stripeService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'metadata' => [
                            'wallet_topup' => true,
                            'user_id' => $user->id,
                            'transaction_id' => $transaction->id
                        ]
                    ]);
                    break;
                case 'paypal':
                    $paypalService = app(\App\Services\PaymentGateways\PayPalPaymentService::class);
                    $result = $paypalService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'metadata' => [
                            'wallet_topup' => true,
                            'user_id' => $user->id,
                            'transaction_id' => $transaction->id
                        ]
                    ]);
                    break;
                case 'mir':
                    $mirService = app(\App\Services\PaymentGateways\MirPaymentService::class);
                    $result = $mirService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'metadata' => [
                            'wallet_topup' => true,
                            'user_id' => $user->id,
                            'transaction_id' => $transaction->id
                        ]
                    ]);
                    break;
                case 'nowpayments':
                    $nowService = app(\App\Services\PaymentGateways\NowPaymentsService::class);
                    $result = $nowService->createPayment([
                        'amount' => $amount,
                        'currency' => $currency,
                        'order_id' => 'WTU-' . now()->format('YmdHis') . '-' . substr(md5(uniqid('', true)),0,6),
                        'description' => 'Wallet Top-up',
                        'crypto_currency' => null,
                        'metadata' => [
                            'wallet_topup' => true,
                            'user_id' => $user->id,
                            'transaction_id' => $transaction->id
                        ]
                    ]);
                    break;
                default:
                    return response()->json(['success' => false, 'error' => 'Unsupported gateway for wallet top-up'], 400);
            }
            return response()->json([
                'success' => true,
                'error' => null,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'gateway' => $gateway,
                    'payment' => $result['data'] ?? $result
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Wallet top-up failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Wallet top-up failed. Please try again.',
                'data' => []
            ], 500);
        }
    }
    /**
     * Unified webhook handler for all gateways
     */
    public function handleWebhook(Request $request, $gateway): JsonResponse
    {
        try {
            $payload = $request->all();
            $gateway = strtolower($gateway);
            // Stripe webhook
            if ($gateway === 'stripe') {
                $eventType = $payload['type'] ?? null;
                if ($eventType === 'checkout.session.completed') {
                    $session = $payload['data']['object'] ?? [];
                    $orderId = $session['metadata']['order_id'] ?? null;
                    $invoiceId = $session['metadata']['invoice_id'] ?? null;
                    $transactionId = $session['metadata']['transaction_id'] ?? null;
                    if ($orderId) {
                        $order = Order::find($orderId);
                        if ($order) {
                            $order->update(['payment_status' => 'paid']);
                            $order->invoice?->update(['status' => 'paid']);
                            \App\Jobs\ProcessXuiOrder::dispatch($order);
                        }
                    }
                    if ($transactionId) {
                        $transaction = \App\Models\WalletTransaction::find($transactionId);
                        if ($transaction) {
                            $transaction->update(['status' => 'completed']);
                            $wallet = $transaction->wallet;
                            $wallet->increment('balance', $transaction->amount);
                        }
                    }
                }
            }
            // NowPayments webhook
            elseif ($gateway === 'nowpayments') {
                $orderId = $payload['order_id'] ?? null;
                $paymentStatus = $payload['payment_status'] ?? null;
                $transactionId = $payload['transaction_id'] ?? null;
                if ($orderId) {
                    $order = Order::find($orderId);
                    if ($order) {
                        switch ($paymentStatus) {
                            case 'finished':
                            case 'confirmed':
                                $order->update(['payment_status' => 'paid']);
                                $order->invoice?->update(['status' => 'paid']);
                                if ($order->payment_status === 'paid') {
                                    \App\Jobs\ProcessXuiOrder::dispatch($order);
                                }
                                break;
                            case 'failed':
                            case 'expired':
                                $order->update(['payment_status' => 'failed']);
                                $order->invoice?->update(['status' => 'failed']);
                                break;
                            case 'waiting':
                            case 'confirming':
                                $order->update(['payment_status' => 'pending']);
                                $order->invoice?->update(['status' => 'pending']);
                                break;
                        }
                    }
                }
                if ($transactionId) {
                    $transaction = \App\Models\WalletTransaction::find($transactionId);
                    if ($transaction && in_array($paymentStatus, ['finished', 'confirmed'])) {
                        $transaction->update(['status' => 'completed']);
                        $wallet = $transaction->wallet;
                        $wallet->increment('balance', $transaction->amount);
                    }
                }
            }
            // PayPal webhook (stub)
            elseif ($gateway === 'paypal') {
                // Implement PayPal webhook logic as needed
            }
            // Mir webhook (stub)
            elseif ($gateway === 'mir') {
                // Implement Mir webhook logic as needed
            }
            return response()->json([
                'success' => true,
                'error' => null,
                'data' => ['message' => 'Webhook processed.']
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed.',
                'data' => []
            ], 500);
        }
    }
    /**
     * Unified refund endpoint
     */
    public function refundPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'amount' => 'nullable|numeric',
        ]);
        try {
            $transactionId = $validated['transaction_id'];
            $amount = $validated['amount'] ?? null;
            $transaction = \App\Models\WalletTransaction::find($transactionId);
            if (!$transaction) {
                return response()->json(['success' => false, 'error' => 'Transaction not found'], 404);
            }
            $gateway = $transaction->payment_method;
            switch ($gateway) {
                case 'stripe':
                    $stripeService = app(\App\Services\PaymentGateways\StripePaymentService::class);
                    $result = $stripeService->refundPayment($transaction->reference, $amount);
                    break;
                case 'paypal':
                    $paypalService = app(\App\Services\PaymentGateways\PayPalPaymentService::class);
                    $result = $paypalService->refundPayment($transaction->reference, $amount);
                    break;
                case 'mir':
                    $mirService = app(\App\Services\PaymentGateways\MirPaymentService::class);
                    $result = $mirService->refundPayment($transaction->reference, $amount);
                    break;
                case 'nowpayments':
                    $nowService = app(\App\Services\PaymentGateways\NowPaymentsService::class);
                    $result = $nowService->refundPayment($transaction->reference, $amount);
                    break;
                case 'wallet':
                    $wallet = $transaction->wallet;
                    $wallet->increment('balance', abs($transaction->amount));
                    $transaction->update(['status' => 'refunded']);
                    $result = ['success' => true, 'message' => 'Wallet refund processed'];
                    break;
                default:
                    return response()->json(['success' => false, 'error' => 'Unsupported gateway for refund'], 400);
            }
            return response()->json([
                'success' => true,
                'error' => null,
                'data' => [
                    'refund' => $result['data'] ?? $result,
                    'transaction_id' => $transactionId
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'transaction_id' => $validated['transaction_id'],
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Refund failed.',
                'data' => []
            ], 500);
        }
    }
    /**
     * Get all available gateways and payment methods
     */
    public function getAvailableGateways(): JsonResponse
    {
        $gateways = [
            'stripe' => [
                'enabled' => !empty(config('services.stripe.secret')),
                'name' => 'Stripe',
                'icon' => '<svg class="inline w-6 h-6 text-blue-500"><!-- Stripe SVG --></svg>',
                'description' => 'Pay securely with your credit or debit card',
                'fee' => 0.0,
            ],
            'paypal' => [
                'enabled' => !empty(config('services.paypal.client_id')),
                'name' => 'PayPal',
                'icon' => '<svg class="inline w-6 h-6 text-blue-700"><!-- PayPal SVG --></svg>',
                'description' => 'Pay easily using your PayPal account',
                'fee' => 0.0,
            ],
            'mir' => [
                'enabled' => !empty(config('services.mir.api_key')),
                'name' => 'Mir',
                'icon' => '<svg class="inline w-6 h-6 text-green-600"><!-- Mir SVG --></svg>',
                'description' => 'Pay with Mir card (Russia only)',
                'fee' => 0.0,
            ],
            'nowpayments' => [
                'enabled' => !empty(config('services.nowpayments.key')),
                'name' => 'Cryptocurrency',
                'icon' => '<svg class="inline w-6 h-6 text-yellow-500"><!-- Crypto SVG --></svg>',
                'description' => 'Pay with Bitcoin, Ethereum, and other cryptocurrencies',
                'fee' => 0.0,
            ],
            'wallet' => [
                'enabled' => true,
                'name' => 'Wallet Balance',
                'icon' => '<svg class="inline w-6 h-6 text-gray-500"><!-- Wallet SVG --></svg>',
                'description' => 'Pay directly from your wallet balance',
                'fee' => 0.0,
            ],
        ];

        // Always enable nowpayments if all others are disabled
        $allDisabled = true;
        foreach ($gateways as $key => $gateway) {
            if ($key !== 'nowpayments' && $key !== 'wallet' && $gateway['enabled']) {
                $allDisabled = false;
                break;
            }
        }
        if ($allDisabled) {
            $gateways['nowpayments']['enabled'] = true;
        }

        // Log the gateway response for debugging
        \Log::info('Payment gateways response', ['gateways' => $gateways]);

        return response()->json([
            'success' => true,
            'error' => null,
            'data' => $gateways
        ]);
    }

    /**
     * Create an invoice
     */
    public function createInvoice(CreatePaymentRequest $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($request->validated()['order_id']);
            
            // Ensure user owns the order
            if ($order->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to order.');
            }

            // Check if order already has an invoice
            if ($order->invoice()->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invoice already exists for this order'
                ], 422);
            }

            $data = [
                'price_amount' => $order->grand_amount,
                'price_currency' => 'usd',
                'order_id' => (string) $order->id,
                'order_description' => 'Payment for Order #' . $order->id,
                'pay_currency' => strtolower($request->validated()['currency']),
                'ipn_callback_url' => config('app.url') . '/api/payment/webhooks/nowpayments',
                'success_url' => url('/api/payment/success?order=' . $order->id),
                'cancel_url' => url('/api/payment/cancel?order=' . $order->id),
                'partially_paid_url' => url('/api/payment/cancel?order=' . $order->id),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
            ];

            $invoice = Nowpayments::createInvoice($data);

            // Validate invoice creation
            if (!isset($invoice['invoice_url'])) {
                Log::error('Invoice creation failed - no invoice URL', [
                    'order_id' => $order->id,
                    'response' => $invoice,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invoice creation failed'
                ], 500);
            }

            // Store invoice details locally
            Invoice::create([
                'order_id' => $order->id,
                'payment_id' => $invoice['payment_id'] ?? null,
                'invoice_url' => $invoice['invoice_url'],
                'status' => 'pending',
                'amount' => $order->grand_amount,
                'currency' => $request->validated()['currency'],
            ]);

            Log::info('Invoice created successfully', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'invoice_url' => $invoice['invoice_url'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'order_id' => $request->validated()['order_id'] ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Invoice creation failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Get the status of a payment by Order ID
     */
    public function getPaymentStatusByOrder(string $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);
            
            // Ensure user owns the order or is admin
            if ($order->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                abort(403, 'Unauthorized access to order.');
            }

            if (!$order->invoice) {
                return response()->json([
                    'success' => false,
                    'error' => 'No payment found for this order'
                ], 404);
            }

            $paymentId = $order->invoice->payment_id;
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No payment ID found'
                ], 404);
            }

            // Cache the payment status for 30 seconds to avoid excessive API calls
            $cacheKey = "payment_status_{$paymentId}";
            $status = Cache::remember($cacheKey, 30, function () use ($paymentId) {
                return Nowpayments::getPaymentStatus($paymentId);
            });

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to check payment status'
            ], 500);
        }
    }

    /**
     * Get the status of a payment
     */
    public function getPaymentStatus(string $paymentId): JsonResponse
    {
        try {
            // Verify user has permission to access this payment
            $invoice = Invoice::where('payment_id', $paymentId)->first();
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'error' => 'Payment not found'
                ], 404);
            }

            $order = $invoice->order;
            if ($order->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                abort(403, 'Unauthorized access to payment.');
            }

            // Cache the payment status for 30 seconds
            $cacheKey = "payment_status_{$paymentId}";
            $status = Cache::remember($cacheKey, 30, function () use ($paymentId) {
                return Nowpayments::getPaymentStatus($paymentId);
            });

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $paymentId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to check payment status'
            ], 500);
        }
    }

    /**
     * Get available currencies
     */
    public function getCurrencies(): JsonResponse
    {
        try {
            // Cache currencies for 1 hour as they don't change frequently
            $currencies = Cache::remember('nowpayments_currencies', 3600, function () {
                return Nowpayments::getCurrencies();
            });

            return response()->json([
                'success' => true,
                'data' => $currencies
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch currencies', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch currencies'
            ], 500);
        }
    }

    /**
     * Get the minimum payment amount for a specific pair
     */
    public function getMinimumPaymentAmount(string $fromCurrency, string $toCurrency): JsonResponse
    {
        try {
            // Validate currencies
            $allowedCurrencies = ['USD', 'EUR', 'GBP', 'BTC', 'ETH', 'XMR', 'LTC'];
            $fromCurrency = strtoupper($fromCurrency);
            $toCurrency = strtoupper($toCurrency);

            if (!in_array($fromCurrency, $allowedCurrencies) || !in_array($toCurrency, $allowedCurrencies)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid currency pair'
                ], 400);
            }

            // Cache minimum amounts for 30 minutes
            $cacheKey = "min_amount_{$fromCurrency}_{$toCurrency}";
            $minimumAmount = Cache::remember($cacheKey, 1800, function () use ($fromCurrency, $toCurrency) {
                return Nowpayments::getMinimumPaymentAmount($fromCurrency, $toCurrency);
            });

            return response()->json([
                'success' => true,
                'data' => $minimumAmount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch minimum payment amount', [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch minimum payment amount'
            ], 500);
        }
    }

    /**
     * Get estimate price for an amount in different pairs
     */
    public function getEstimatePrice(EstimatePriceRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = [
                'amount' => $validated['amount'],
                'currency_from' => strtolower($validated['currency_from']),
                'currency_to' => strtolower($validated['currency_to']),
            ];

            // Cache estimates for 5 minutes to reduce API calls
            $cacheKey = "estimate_" . md5(json_encode($data));
            $estimate = Cache::remember($cacheKey, 300, function () use ($data) {
                return Nowpayments::getEstimatePrice($data);
            });

            return response()->json([
                'success' => true,
                'data' => $estimate
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get price estimate', [
                'request_data' => $request->validated(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to get price estimate'
            ], 500);
        }
    }

    /**
     * Handle Nowpayments webhook
     */
    public function handleWebhookNowPayments(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature for security
            $payload = $request->getContent();
            $signature = $request->header('X-Nowpayments-Sig');
            $secret = config('services.nowpayments.webhook_secret');

            if (!$this->verifyWebhookSignature($payload, $signature, $secret)) {
                Log::warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'payload' => $payload,
                ]);
                return response()->json(['success' => false, 'error' => 'Invalid signature'], 401);
            }

            $data = $request->json()->all();
            
            // Validate required fields
            if (!isset($data['order_id']) || !isset($data['payment_status'])) {
                Log::warning('Invalid webhook payload', ['data' => $data]);
                return response()->json(['success' => false, 'error' => 'Invalid payload'], 400);
            }

            $order = Order::find($data['order_id']);
            if (!$order) {
                Log::warning('Webhook for non-existent order', ['order_id' => $data['order_id']]);
                return response()->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            // Update order status based on payment status
            switch ($data['payment_status']) {
                case 'finished':
                case 'confirmed':
                    $order->update(['payment_status' => 'paid']);
                    $order->invoice?->update(['status' => 'paid']);
                    
                    // Trigger order processing job
                    if ($order->payment_status === 'paid') {
                        \App\Jobs\ProcessXuiOrder::dispatch($order);
                    }
                    
                    Log::info('Payment confirmed via webhook', [
                        'order_id' => $order->id,
                        'payment_id' => $data['payment_id'] ?? null,
                    ]);
                    break;

                case 'failed':
                case 'expired':
                    $order->update(['payment_status' => 'failed']);
                    $order->invoice?->update(['status' => 'failed']);
                    
                    Log::info('Payment failed via webhook', [
                        'order_id' => $order->id,
                        'payment_id' => $data['payment_id'] ?? null,
                        'status' => $data['payment_status'],
                    ]);
                    break;

                case 'waiting':
                case 'confirming':
                    $order->update(['payment_status' => 'pending']);
                    $order->invoice?->update(['status' => 'pending']);
                    break;
            }

            return response()->json(['success' => true, 'status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent(),
            ]);

            return response()->json(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(string $payload, ?string $signature, string $secret): bool
    {
        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Display invoice details (NowPayments)
     */
    public function showInvoice(string $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);
            
            // Ensure user owns the order or is admin
            if ($order->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                abort(403, 'Unauthorized access to invoice.');
            }

            $invoice = $order->invoice;
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_url' => $invoice->invoice_url,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'created_at' => $invoice->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to show invoice', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to retrieve invoice'
            ], 500);
        }
    }

    /**
     * Show the payment processor UI
     */
    public function showPaymentProcessor(Request $request)
    {
        // Pass type, amount, currency, and order_id to the Livewire component/view
        $type = $request->input('type', 'fiat');
        $amount = $request->input('amount', 0);
        $currency = $request->input('currency', 'USD');
        $orderId = $request->input('order_id');

        // Optionally fetch order if order_id is present
        $order = null;
        if ($orderId) {
            $order = \App\Models\Order::find($orderId);
        }

        // Render the Livewire payment processor component
        return view('livewire.components.payment-processor', [
            'order' => $order,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    /**
     * Get payment methods
     */
    public static function getPaymentMethods()
    {
        return Cache::remember('payment_methods', 3600, function () {
            return PaymentMethod::where('is_active', true)->get();
        });
    }
}
