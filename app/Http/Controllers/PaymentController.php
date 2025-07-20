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
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:60,1')->only(['createCryptoPayment', 'createInvoice']);
        $this->middleware('throttle:30,1')->only(['getEstimatePrice']);
    }

    /**
     * Create a crypto payment
     */
    public function createCryptoPayment(CreatePaymentRequest $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($request->validated()['order_id']);
            
            // Ensure user owns the order
            if ($order->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to order.');
            }

            // Check if order already has a payment
            if ($order->payment_status === 'paid') {
                return response()->json(['error' => 'Order already paid'], 422);
            }

            $data = [
                'price_amount' => $order->grand_amount,
                'price_currency' => 'usd',
                'order_id' => (string) $order->id,
                'order_description' => 'Order #' . $order->id,
                'pay_currency' => strtolower($request->validated()['currency']),
                'ipn_callback_url' => config('app.url') . '/api/webhooks/nowpayments',
                'success_url' => route('success', ['order' => $order->id]),
                'cancel_url' => route('cancel', ['order' => $order->id]),
                'partially_paid_url' => route('cancel', ['order' => $order->id]),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
            ];

            $paymentDetails = Nowpayments::createPayment($data);

            // Log payment creation
            Log::info('Crypto payment created', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'amount' => $order->grand_amount,
                'payment_id' => $paymentDetails['payment_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $paymentDetails
            ]);

        } catch (\Exception $e) {
            Log::error('Crypto payment creation failed', [
                'order_id' => $request->validated()['order_id'] ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment creation failed. Please try again.'
            ], 500);
        }
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
                'ipn_callback_url' => config('app.url') . '/api/webhooks/nowpayments',
                'success_url' => route('success', ['order' => $order->id]),
                'cancel_url' => route('cancel', ['order' => $order->id]),
                'partially_paid_url' => route('cancel', ['order' => $order->id]),
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
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = $request->json()->all();
            
            // Validate required fields
            if (!isset($data['order_id']) || !isset($data['payment_status'])) {
                Log::warning('Invalid webhook payload', ['data' => $data]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            $order = Order::find($data['order_id']);
            if (!$order) {
                Log::warning('Webhook for non-existent order', ['order_id' => $data['order_id']]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Update order status based on payment status
            switch ($data['payment_status']) {
                case 'finished':
                case 'confirmed':
                    $order->update(['payment_status' => 'paid']);
                    $order->invoice?->update(['status' => 'paid']);
                    
                    // Trigger order processing job
                    \App\Jobs\ProcessXuiOrder::dispatch($order);
                    
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

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
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
     * Get payment methods
     */
    public static function getPaymentMethods()
    {
        return Cache::remember('payment_methods', 3600, function () {
            return PaymentMethod::where('is_active', true)->get();
        });
    }
}
