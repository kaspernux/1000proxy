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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page
     */
    public function index()
    {
        $customer = Auth::user();
        $order_items = CartManagement::getCartItemsFromCookie();
        $grand_amount = CartManagement::calculateGrandTotal($order_items);
        $payment_methods = PaymentMethod::where('is_active', true)->get();

        // Redirect to servers if cart is empty
        if (count($order_items) === 0) {
            return redirect('/servers')->with('warning', 'Your cart is empty. Please add items to proceed with checkout.');
        }

        return view('checkout.index', compact(
            'customer',
            'order_items',
            'grand_amount',
            'payment_methods'
        ));
    }

    /**
     * Process the checkout and create order
     */
    public function store(CheckoutRequest $request)
    {
        $validatedData = $request->validated();
        
        DB::beginTransaction();

        try {
            // Get cart items and calculate totals
            $order_items = CartManagement::getCartItemsFromCookie();
            $grand_amount = CartManagement::calculateGrandTotal($order_items);
            
            if (count($order_items) === 0) {
                return redirect('/servers')->with('error', 'Your cart is empty.');
            }

            // Get payment method
            $paymentMethod = PaymentMethod::where('slug', $validatedData['payment_method'])->firstOrFail();

            // Create Order
            $order = Order::create([
                'customer_id'    => auth()->id(),
                'grand_amount'   => $grand_amount,
                'currency'       => 'usd',
                'payment_method' => $paymentMethod->id,
                'order_status'   => 'new',
                'payment_status' => 'pending',
                'notes'          => 'Order placed by ' . auth()->user()->name,
            ]);

            // Create Invoice
            $invoice = Invoice::create([
                'customer_id'      => $order->customer_id,
                'order_id'         => $order->id,
                'payment_method_id'=> $paymentMethod->id,
                'price_amount'     => $grand_amount,
                'price_currency'   => 'usd',
                'pay_amount'       => $grand_amount,
                'pay_currency'     => $paymentMethod->default_currency ?? 'usd',
                'order_description'=> $order->notes,
                'invoice_url'      => '',
                'success_url'      => route('checkout.success', ['order' => $order->id]),
                'cancel_url'       => route('checkout.cancel',  ['order' => $order->id]),
                'is_fixed_rate'    => true,
                'is_fee_paid_by_user' => true,
            ]);

            // Attach order items
            foreach ($order_items as $item) {
                $plan = ServerPlan::findOrFail($item['server_plan_id']);
                $order->items()->create([
                    'server_plan_id' => $plan->id,
                    'quantity'       => $item['quantity'],
                    'unit_amount'    => $plan->price,
                    'total_amount'   => $plan->price * $item['quantity'],
                ]);
            }

            // Process payment based on method
            $redirect_url = $this->processPayment($paymentMethod, $order, $invoice, $grand_amount);

            DB::commit();
            
            // Clear cart
            CartManagement::clearCartItems();

            // Send order confirmation email
            Mail::to(Auth::user())->send(new OrderPlaced($order));

            // Redirect to payment or success page
            if ($redirect_url) {
                return redirect($redirect_url);
            }

            return redirect()->route('checkout.success', ['order' => $order->id])
                ->with('success', 'Order placed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withInput()->with('error', 'Error processing your order: ' . $e->getMessage());
        }
    }

    /**
     * Process payment based on the selected method
     */
    private function processPayment(PaymentMethod $paymentMethod, Order $order, Invoice $invoice, $grandAmount)
    {
        $customer = Auth::user();
        $wallet = $customer->getWallet();
        $redirect_url = '';

        switch ($paymentMethod->slug) {
            case 'wallet':
                return $this->processWalletPayment($customer, $wallet, $order, $invoice, $grandAmount);
                
            case 'stripe':
                return $this->processStripePayment($customer, $wallet, $order, $invoice, $grandAmount);
                
            case 'nowpayments':
                return $this->processNowPaymentsPayment($customer, $wallet, $order, $invoice, $grandAmount);
                
            default:
                throw new \Exception('Invalid payment method selected.');
        }
    }

    /**
     * Process wallet payment
     */
    private function processWalletPayment($customer, $wallet, $order, $invoice, $grandAmount)
    {
        // Check wallet balance
        if (!$wallet || !$customer->payFromWallet($grandAmount, 'Order #' . $order->id)) {
            throw new \Exception('Insufficient balance in your USD wallet.');
        }

        // Log wallet transaction
        $transaction = $wallet->transactions()->create([
            'wallet_id'     => $wallet->id,
            'customer_id'   => $customer->id,
            'type'          => 'withdrawal',
            'amount'        => -abs($grandAmount),
            'status'        => 'completed',
            'reference'     => 'order_' . $order->id,
            'description'   => 'Payment for Order #' . $order->id,
            'metadata'      => ['order_id' => $order->id, 'method' => 'wallet'],
        ]);

        // Update invoice
        $invoice->update([
            'wallet_transaction_id' => $transaction->id,
            'invoice_url' => route('checkout.success', ['order' => $order->id]),
        ]);

        // Mark order as paid
        $order->markAsPaid(route('checkout.success', ['order' => $order->id]));

        // Process XUI clients
        $this->processXui($order);

        return null; // Will redirect to success page
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment($customer, $wallet, $order, $invoice, $grandAmount)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        
        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $customer->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => intval($grandAmount * 100),
                    'product_data' => ['name' => 'Order #' . $order->id],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('checkout.success', ['order' => $order->id, 'session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url'  => route('checkout.cancel', ['order' => $order->id]),
            'metadata' => [ 
                'order_id' => $order->id,
            ],
        ]);

        // Log wallet transaction (pending)
        $transaction = $wallet->transactions()->create([
            'wallet_id'     => $wallet->id,
            'customer_id'   => $customer->id,
            'type'          => 'withdrawal',
            'amount'        => -abs($grandAmount),
            'status'        => 'pending',
            'reference'     => 'order_' . $order->id,
            'description'   => 'Stripe payment for Order #' . $order->id,
            'metadata'      => [
                'order_id' => $order->id,
                'method'   => 'stripe',
                'stripe_session_id' => $session->id,
            ],
        ]);

        // Update invoice
        $invoice->update([
            'wallet_transaction_id' => $transaction->id,
            'invoice_url' => $session->url,
        ]);

        // Mark order as processing
        $order->markAsProcessing($session->url);

        return $session->url;
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
                        'expiryTime' => isset($remoteInbound->expiryTime)
                            ? now()->createFromTimestampMs($remoteInbound->expiryTime)
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
                'expiryTime' => isset($inbound->expiryTime) ? now()->createFromTimestampMs($inbound->expiryTime) : null,
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

    /**
     * Show success page
     */
    public function success(Order $order, Request $request)
    {
        // Verify order belongs to current user
        if ($order->customer_id !== auth()->id()) {
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
        if ($order->customer_id !== auth()->id()) {
            abort(404);
        }

        return view('checkout.cancel', compact('order'));
    }
}