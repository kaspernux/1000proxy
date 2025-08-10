<?php

namespace App\Http\Controllers;

use Exception;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Invoice;
use App\Mail\OrderPlaced;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use Stripe\Checkout\Session;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\CheckoutRequest;
use App\Services\XUIService;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use App\Services\PaymentGateways\NowPaymentsService;
use App\Services\PaymentGateways\MirPaymentService;
use App\Services\PaymentGateways\StripePaymentService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page using Livewire component
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $order_items = CartManagement::getCartItemsFromCookie();

        // Redirect to servers if cart is empty
        if (count($order_items) === 0) {
            return redirect('/servers')->with('warning', 'Your cart is empty. Please add items to proceed with checkout.');
        }

        // Render the Livewire component properly
        return view('layouts.app')->with([
            'slot' => app(\App\Livewire\CheckoutPage::class)->render()
        ]);
    }

    /**
     * Process the checkout and create order
     */
    public function store(CheckoutRequest $request)
    {
        $validatedData = $request->validated();
        
        DB::beginTransaction();

        try {
            // Get cart items from request data (sent by Livewire)
            $cart_items = $validatedData['cart_items'] ?? [];
            $order_summary = $validatedData['order_summary'] ?? [];

            // Robust cart validation
            if (empty($cart_items) || count($cart_items) === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Your cart is empty or could not be loaded. Please refresh and try again.'
                ], 400);
            }

            // Payment method validation
            if (!isset($validatedData['payment_method']) || empty($validatedData['payment_method'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No payment method selected. Please choose a payment method.'
                ], 400);
            }

            $customer = Auth::guard('customer')->user();

            // Resolve payment method model (required for invoice FK & order column expects id NOT slug)
            $paymentMethodModel = PaymentMethod::where('slug', $validatedData['payment_method'])->first();
            if (!$paymentMethodModel) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid payment method selected.'
                ], 400);
            }

            // Create Order
            try {
                $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'payment_status' => 'pending',
                // Store the numeric id (column is unsignedBigInteger)
                'payment_method' => $paymentMethodModel->id,
                // Original schema requires grand_amount (NOT NULL)
                'grand_amount' => $order_summary['total'] ?? 0,
                'subtotal' => $order_summary['subtotal'] ?? 0,
                'tax_amount' => $order_summary['tax'] ?? 0,
                'shipping_amount' => $order_summary['shipping'] ?? 0,
                'discount_amount' => $order_summary['discount'] ?? 0,
                'total_amount' => $order_summary['total'] ?? 0,
                'currency' => 'USD',
                'billing_first_name' => $validatedData['first_name'] ?? '',
                'billing_last_name' => $validatedData['last_name'] ?? '',
                'billing_email' => $validatedData['email'] ?? '',
                'billing_phone' => $validatedData['phone'] ?? '',
                'billing_company' => $validatedData['company'] ?? null,
                'billing_address' => $validatedData['address'] ?? '',
                'billing_city' => $validatedData['city'] ?? '',
                'billing_state' => $validatedData['state'] ?? '',
                'billing_postal_code' => $validatedData['postal_code'] ?? '',
                'billing_country' => $validatedData['country'] ?? '',
                'coupon_code' => $validatedData['coupon_code'] ?? null,
                'notes' => 'Order placed via enhanced checkout system',
                ]);
            } catch (\Throwable $t) {
                Log::error('Order model create failed', [
                    'error' => $t->getMessage(),
                    'trace' => str_starts_with($t->getMessage(), 'SQLSTATE') ? null : $t->getTraceAsString(),
                    'summary' => $order_summary,
                    'payment_method_id' => $paymentMethodModel->id,
                ]);
                throw $t; // bubble up to outer catch
            }

            // Create order items (relation is items())
            foreach ($cart_items as $item) {
                if (!isset($item['server_plan_id'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => 'Cart item missing server plan. Please refresh and try again.'
                    ], 400);
                }
                $plan = ServerPlan::findOrFail($item['server_plan_id']);
                $quantity = $item['quantity'] ?? 1;
                $unitAmount = $item['unit_price'] ?? $item['unit_amount'] ?? $plan->price;
                $order->items()->create([
                    'server_plan_id' => $item['server_plan_id'],
                    'quantity' => $quantity,
                    'unit_amount' => $unitAmount,
                    'total_amount' => $item['total_price'] ?? $item['total_amount'] ?? ($unitAmount * $quantity),
                ]);
            }

            // Create Invoice (payment_method_id required and not nullable)
            $invoice = Invoice::create([
                'customer_id' => $order->customer_id,
                'payment_method_id' => $paymentMethodModel->id,
                'order_id' => $order->id,
                'price_amount' => $order->total_amount,
                'price_currency' => 'USD',
                'pay_amount' => $order->total_amount,
                'pay_currency' => 'USD',
                'order_description' => $order->notes,
                'invoice_url' => '',
                'success_url' => route('checkout.success', ['order' => $order->id]),
                'cancel_url' => route('checkout.cancel', ['order' => $order->id]),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
            ]);

            // Process payment based on method
            $paymentResult = $this->processEnhancedPayment($validatedData, $order, $invoice);

            if ($paymentResult['success']) {
                DB::commit();
                if (isset($paymentResult['redirect_url'])) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $paymentResult['redirect_url'],
                        'order' => $order->toArray(),
                        'payment_type' => 'external'
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'order' => $order->toArray(),
                        'transaction_id' => $paymentResult['transaction_id'] ?? null,
                        'payment_type' => 'internal'
                    ]);
                }
            } else {
                throw new Exception($paymentResult['error'] ?? 'Payment processing failed');
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Enhanced checkout failed', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'payment_method' => $validatedData['payment_method'] ?? null,
                'ip' => request()->ip()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process enhanced payment based on the selected method
     */
    private function processEnhancedPayment($validatedData, Order $order, Invoice $invoice)
    {
        $customer = Auth::guard('customer')->user();
        $wallet = $customer->wallet;

            switch ($validatedData['payment_method']) {
                case 'wallet':
                    return $this->processEnhancedWalletPayment($customer, $wallet, $order, $invoice);
                case 'stripe':
                    return $this->processEnhancedStripePayment($customer, $order, $invoice, $validatedData);
                case 'crypto':
                    return $this->processEnhancedCryptoPayment($customer, $order, $invoice, $validatedData);
                case 'mir':
                    return $this->processEnhancedMirPayment($customer, $order, $invoice, $validatedData);
                default:
                    throw new \Exception('Invalid payment method selected.');
            }
    }
    

    /**
     * Process enhanced wallet payment
     */
    private function processEnhancedWalletPayment($customer, $wallet, Order $order, Invoice $invoice)
    {
        // Check if customer has a wallet
        if (!$wallet) {
            throw new \Exception('Customer wallet not found. Please contact support.');
        }

        // Check wallet balance
        if ($wallet->balance < $order->total_amount) {
            throw new \Exception(
                'Insufficient wallet balance. Current balance: $' . 
                number_format($wallet->balance, 2) . 
                ', Required: $' . number_format($order->total_amount, 2)
            );
        }

        // Use Customer model's payFromWallet method
        if (!$customer->payFromWallet($order->total_amount, "Order #{$order->order_number}")) {
            throw new \Exception('Failed to process wallet payment. Please try again.');
        }

        // Log wallet transaction
        $transaction = $wallet->transactions()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'type' => 'withdrawal',
            'amount' => -abs($order->total_amount),
            'status' => 'completed',
            'reference' => 'order_' . $order->id,
            'description' => "Payment for Order #{$order->order_number}",
            'metadata' => [
                'order_id' => $order->id,
                'payment_method' => 'wallet',
                'order_number' => $order->order_number
            ],
        ]);

        // Update invoice
        $invoice->update([
            'wallet_transaction_id' => $transaction->id,
            'invoice_url' => route('checkout.success', ['order' => $order->id]),
            // Normalize to 'paid' for internal consistency
            'payment_status' => 'paid',
        ]);

        // Mark order as paid
        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_transaction_id' => $transaction->id,
        ]);

        // Process XUI clients for immediate provisioning
        $this->processXui($order);

        return [
            'success' => true,
            'transaction_id' => $transaction->id,
            'message' => 'Payment completed successfully'
        ];
    }

    /**
     * Process enhanced Stripe payment
     */
    private function processEnhancedStripePayment($customer, Order $order, Invoice $invoice, $validatedData)
    {
        try {
            $stripeService = new StripePaymentService();
            
            $paymentData = [
                'amount' => $order->total_amount,
                'currency' => 'USD',
                'order_id' => $order->order_number,
                'description' => "Proxy Order #{$order->order_number}",
                'customer_email' => $validatedData['email'],
                'success_url' => route('checkout.success', $order->id),
                'cancel_url' => route('checkout.cancel', $order->id),
                'metadata' => [
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                ]
            ];

            $paymentResult = $stripeService->createPayment($paymentData);

            if ($paymentResult['success'] && isset($paymentResult['payment_url'])) {
                // Update invoice
                $invoice->update([
                    'invoice_url' => $paymentResult['payment_url'],
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                ]);

                // Update order
                $order->update([
                    'payment_details' => [
                        'provider' => 'stripe',
                        'payment_id' => $paymentResult['payment_id'] ?? null,
                        'payment_url' => $paymentResult['payment_url'],
                    ]
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $paymentResult['payment_url'],
                    'transaction_id' => $paymentResult['payment_id'] ?? 'STRIPE-' . uniqid()
                ];
            }

            throw new \Exception($paymentResult['error'] ?? 'Failed to create Stripe payment');

        } catch (\Exception $e) {
            Log::error('Enhanced Stripe payment failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'customer_id' => $customer->id,
            ]);
            
            return [
                'success' => false,
                'error' => 'Stripe payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process enhanced cryptocurrency payment
     */
    private function processEnhancedCryptoPayment($customer, Order $order, Invoice $invoice, $validatedData)
    {
        try {
            $nowPaymentsService = new NowPaymentsService();
            
            $paymentData = [
                'amount' => $order->total_amount,
                'currency' => 'USD',
                'crypto_currency' => $validatedData['crypto_currency'],
                'order_id' => $order->order_number,
                'description' => "Proxy Order #{$order->order_number}",
                'success_url' => route('checkout.success', $order->id),
                'cancel_url' => route('checkout.cancel', $order->id),
                'customer_email' => $validatedData['email'],
            ];

            $paymentResult = $nowPaymentsService->createPayment($paymentData);

            if ($paymentResult['success'] && isset($paymentResult['payment_url'])) {
                // Update invoice
                $invoice->update([
                    'invoice_url' => $paymentResult['payment_url'],
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                    'pay_currency' => $validatedData['crypto_currency'],
                    'pay_amount' => $paymentResult['crypto_amount'] ?? null,
                ]);

                // Update order
                $order->update([
                    'payment_details' => [
                        'provider' => 'nowpayments',
                        'payment_id' => $paymentResult['payment_id'] ?? null,
                        'payment_url' => $paymentResult['payment_url'],
                        'crypto_currency' => $validatedData['crypto_currency'],
                        'crypto_amount' => $paymentResult['crypto_amount'] ?? null,
                    ]
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $paymentResult['payment_url'],
                    'transaction_id' => $paymentResult['payment_id'] ?? 'CRYPTO-' . uniqid()
                ];
            }

            throw new \Exception($paymentResult['error'] ?? 'Failed to create crypto payment');

        } catch (\Exception $e) {
            Log::error('Enhanced crypto payment failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'crypto_currency' => $validatedData['crypto_currency'] ?? null,
                'customer_id' => $customer->id,
            ]);
            
            return [
                'success' => false,
                'error' => 'Crypto payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process enhanced MIR payment
     */
    private function processEnhancedMirPayment($customer, Order $order, Invoice $invoice, $validatedData)
    {
        try {
            $mirService = new MirPaymentService();
            
            // Convert USD to RUB using live exchange rate or fixed rate
            $exchangeRate = 75; // This should be fetched from a real exchange rate API
            $rubAmount = $order->total_amount * $exchangeRate;
            
            $paymentData = [
                'amount' => $rubAmount,
                'currency' => 'RUB',
                'order_id' => $order->order_number,
                'description' => "Proxy Order #{$order->order_number}",
                'success_url' => route('checkout.success', $order->id),
                'cancel_url' => route('checkout.cancel', $order->id),
                'customer_email' => $validatedData['email'],
                'original_amount' => $order->total_amount,
                'original_currency' => 'USD',
                'exchange_rate' => $exchangeRate,
            ];

            $paymentResult = $mirService->createPayment($paymentData);

            if ($paymentResult['success'] && isset($paymentResult['payment_url'])) {
                // Update invoice
                $invoice->update([
                    'invoice_url' => $paymentResult['payment_url'],
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                    'pay_currency' => 'RUB',
                    'pay_amount' => $rubAmount,
                ]);

                // Update order
                $order->update([
                    'payment_details' => [
                        'provider' => 'mir',
                        'payment_id' => $paymentResult['payment_id'] ?? null,
                        'payment_url' => $paymentResult['payment_url'],
                        'rub_amount' => $rubAmount,
                        'exchange_rate' => $exchangeRate,
                        'original_amount' => $order->total_amount,
                        'original_currency' => 'USD',
                    ]
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $paymentResult['payment_url'],
                    'transaction_id' => $paymentResult['payment_id'] ?? 'MIR-' . uniqid()
                ];
            }

            throw new \Exception($paymentResult['error'] ?? 'Failed to create MIR payment');

        } catch (\Exception $e) {
            Log::error('Enhanced MIR payment failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'customer_id' => $customer->id,
            ]);
            
            return [
                'success' => false,
                'error' => 'MIR payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Show success page
     */
    public function success(Order $order)
    {
        // Verify order belongs to current user
        if ($order->customer_id !== Auth::guard('customer')->id()) {
            abort(404);
        }

        return view('checkout.success', compact('order'));
    }

    /**
     * Show cancel page
     */
    public function cancel(Order $order)
    {
        // Verify order belongs to current user
        if ($order->customer_id !== Auth::guard('customer')->id()) {
            abort(404);
        }

        return view('checkout.cancel', compact('order'));
    }

    /**
     * Process NowPayments crypto payment
     */
    private function processNowPaymentsPayment($customer, $wallet, $order, $invoice, $grandAmount)
    {
        $paymentController = new PaymentMethodController();
        $payResult = $paymentController->createInvoiceNowPayments($order);

        if ($payResult['status'] !== 'success' || !isset($payResult['data']['invoice_url'])) {
            Log::error('NowPayments invoice creation failed', [
                'order_id' => $order->id,
                'response' => $payResult,
            ]);
            throw new \Exception('Failed to create crypto payment invoice.');
        }

        $redirect_url = $payResult['data']['invoice_url'];

        // Update order
        $order->update(['payment_invoice_url' => $redirect_url]);

        // Log wallet transaction (pending)
        $transaction = $wallet->transactions()->create([
            'wallet_id'     => $wallet->id,
            'customer_id'   => $customer->id,
            'type'          => 'withdrawal',
            'amount'        => -abs($grandAmount),
            'status'        => 'pending',
            'reference'     => 'order_' . $order->id,
            'description'   => 'NowPayments crypto payment for Order #' . $order->id,
            'payment_id'    => $payResult['data']['payment_id'] ?? null,
            'address'       => $payResult['data']['pay_address'] ?? null,
            'metadata'      => [
                'order_id' => $order->id,
                'method'   => 'nowpayments',
                'invoice_url' => $redirect_url,
            ],
        ]);

        // Update invoice
        $invoice->update([
            'wallet_transaction_id' => $transaction->id,
            'invoice_url' => $redirect_url,
        ]);

        // Mark order as processing
        $order->markAsProcessing($redirect_url);

        return $redirect_url;
    }

    /**
     * Process XUI clients for the order
     */
    protected function processXui(Order $order)
    {
        foreach ($order->items as $item) {
            $plan = $item->serverPlan;
            $xuiService = new XUIService($plan->server_id);
            $inbound_id = $xuiService->getDefaultInboundId();

            for ($i = 0; $i < $item->quantity; $i++) {
                // Create client remotely
                $client = $xuiService->addInboundAccount(
                    $plan->server_id,
                    $xuiService->generateUID(),
                    $inbound_id,
                    now()->addDays($plan->days)->timestamp * 1000,
                    (Str::uuid()) . ' - ' . $plan->name . ' #ID ' . $order->customer_id,
                    $plan->volume,
                    1,
                    $plan->id
                );

                if (!$client || isset($client['error'])) {
                    throw new \Exception("XUI Inbound creation failed: " . json_encode($client));
                }

                try {
                    // Get remote inbound
                    $remoteInbound = collect($xuiService->getInbounds($plan->server_id))
                        ->firstWhere('id', $inbound_id);

                    if (!$remoteInbound) {
                        throw new \Exception("Remote inbound ID {$inbound_id} not found after client creation.");
                    }

                    // Create/update local inbound
                    $localInbound = ServerInbound::updateOrCreate([
                        'server_id' => $plan->server_id,
                        'port' => $remoteInbound->port,
                    ], [
                        'protocol' => $remoteInbound->protocol,
                        'remark' => $remoteInbound->remark ?? '',
                        'enable' => $remoteInbound->enable ?? true,
                        'expiry_time' => isset($remoteInbound->expiry_time)
                            ? now()->createFromTimestampMs($remoteInbound->expiry_time)
                            : null,
                        'settings' => is_string($remoteInbound->settings)
                            ? json_decode($remoteInbound->settings, true)
                            : $remoteInbound->settings,
                        'streamSettings' => is_string($remoteInbound->streamSettings)
                            ? json_decode($remoteInbound->streamSettings, true)
                            : $remoteInbound->streamSettings,
                        'sniffing' => is_string($remoteInbound->sniffing)
                            ? json_decode($remoteInbound->sniffing, true)
                            : $remoteInbound->sniffing,
                        'up' => $remoteInbound->up ?? 0,
                        'down' => $remoteInbound->down ?? 0,
                        'total' => $remoteInbound->total ?? 0,
                    ]);

                    $localInbound->loadMissing('server');

                    // Create local client
                    $clientModel = ServerClient::fromRemoteClient(
                        (array)$client,
                        $localInbound->id,
                        $client['link'] ?? $client['sub_link'] ?? $client['json_link'] ?? null
                    );

                    $clientModel->update(['plan_id' => $plan->id]);

                    Log::info("✅ Created ServerClient for Order #{$order->id}");
                } catch (\Throwable $e) {
                    Log::warning("⚠️ Failed direct ServerClient creation after XUI account creation", [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback sync
            $this->syncInboundsForOrder($order, $plan, $xuiService);
        }

        // Mark order as completed
        $order->markAsCompleted();
    }

    /**
     * Sync inbounds as fallback
     */
    private function syncInboundsForOrder(Order $order, ServerPlan $plan, XUIService $xuiService)
    {
        $remoteInbounds = $xuiService->getInbounds($plan->server_id);
        foreach ($remoteInbounds as $inbound) {
            $localInbound = ServerInbound::updateOrCreate([
                'server_id' => $plan->server_id,
                'port' => $inbound->port,
            ], [
                'protocol' => $inbound->protocol,
                'remark' => $inbound->remark ?? '',
                'enable' => $inbound->enable ?? true,
                'expiry_time' => isset($inbound->expiry_time) ? now()->createFromTimestampMs($inbound->expiry_time) : null,
                'settings' => is_string($inbound->settings) ? json_decode($inbound->settings, true) : $inbound->settings,
                'streamSettings' => is_string($inbound->streamSettings) ? json_decode($inbound->streamSettings, true) : $inbound->streamSettings,
                'sniffing' => is_string($inbound->sniffing) ? json_decode($inbound->sniffing, true) : $inbound->sniffing,
                'up' => $inbound->up ?? 0,
                'down' => $inbound->down ?? 0,
                'total' => $inbound->total ?? 0,
            ]);

            $localInbound->loadMissing('server');

            $clients = (array) ($inbound->settings['clients'] ?? []);
            foreach ($clients as $remoteClient) {
                try {
                    $clientModel = ServerClient::fromRemoteClient(
                        (array)$remoteClient,
                        $localInbound->id,
                        $remoteClient['sub_link'] ?? null
                    );

                    $clientModel->update(['plan_id' => $plan->id]);

                    Log::info("✅ Fallback synced ServerClient {$clientModel->email} for Order #{$order->id}");
                } catch (\Throwable $e) {
                    Log::warning("⚠️ Skipped fallback client creation for Order #{$order->id}", [
                        'client_email' => $remoteClient['email'] ?? 'N/A',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

}