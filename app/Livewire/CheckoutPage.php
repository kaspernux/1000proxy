<?php

namespace App\Livewire;

use alert;
use Exception;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Invoice;
use Livewire\Component;
use App\Mail\OrderPlaced;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use Stripe\Checkout\Session;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PaymentMethodController;
use App\Services\XUIService;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;


#[\Livewire\Attributes\Title('Checkout - 1000 PROXIES')]
class CheckoutPage extends Component
{
    use LivewireAlert;

    public $name;
    public $email;
    public $telegram_id;
    public $phone;
    public $customer;
    public $order_items;
    public $grand_amount;
    public $payment_methods = [];
    public $selectedPaymentMethod; // This will hold the slug

    
    public function placeOrder()
    {
        try {
            $validatedData = $this->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|max:255',
                'phone'                 => 'nullable|string|max:20',
                'telegram_id'           => 'nullable|string|max:255',
                'selectedPaymentMethod' => 'required|exists:payment_methods,slug',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->alert('warning', $e->validator->errors()->first('selectedPaymentMethod'));
            return;
        }

        DB::beginTransaction();

        try {
            // 1) Create Order & Invoice
            $paymentMethod = PaymentMethod::where('slug', $validatedData['selectedPaymentMethod'])->firstOrFail();
            $grandAmount   = CartManagement::calculateGrandTotal($this->order_items);

            $order = Order::create([
                'customer_id'    => auth()->id(),
                'grand_amount'   => $grandAmount,
                'currency'       => 'usd',
                'payment_method' => $paymentMethod->id,
                'order_status'   => 'new',
                'payment_status' => 'pending',
                'notes'          => 'Order placed by ' . auth()->user()->name,
            ]);

            $invoice = Invoice::create([
                'customer_id'      => $order->customer_id,
                'order_id'         => $order->id,
                'payment_method_id'=> $paymentMethod->id,
                'price_amount'     => $grandAmount,
                'price_currency'   => 'usd',
                'pay_amount'       => $grandAmount,
                'pay_currency'     => $paymentMethod->default_currency ?? 'usd',
                'order_description'=> $order->notes,
                'invoice_url'      => '',
                'success_url'      => route('success', ['order' => $order->id]),
                'cancel_url'       => route('cancel',  ['order' => $order->id]),
                'is_fixed_rate'    => true,
                'is_fee_paid_by_user' => true,
            ]);

            // 2) Attach line items
            foreach ($this->order_items as $item) {
                $plan = ServerPlan::findOrFail($item['server_plan_id']);
                $order->items()->create([
                    'server_plan_id' => $plan->id,
                    'quantity'       => $item['quantity'],
                    'unit_amount'    => $plan->price,
                    'total_amount'   => $plan->price * $item['quantity'],
                ]);
            }

            // 3) Choose payment method
            $redirect_url = '';

            if ($paymentMethod->slug === 'wallet') {
                $customer = Auth::user();
                $wallet = $customer->getWallet(); // 👈 assuming this method exists
            
                // 💸 Attempt to debit
                if (! $wallet || !$customer->payFromWallet($grandAmount, 'Order #' . $order->id)) {
                    $this->alert('warning', "Insufficient balance in your USD wallet.");
                    return redirect()->route('wallet.insufficient', ['currency' => 'usd']);
                }
            
                // 🧾 Log the wallet transaction (so we can link it to invoice)
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
                
            
                // 🔗 Update the invoice to include this transaction
                $invoice->update([
                    'wallet_transaction_id' => $transaction->id,
                    'invoice_url' => $redirect_url,
                ]);
            
                $order->markAsPaid($redirect_url);
            
                // ✅ Create the proxy clients
                $this->processXui($order);
            }
            
            elseif ($paymentMethod->slug === 'stripe') {
                $wallet = Auth::user()->wallet;
                $customer = Auth::user();

                Stripe::setApiKey(env('STRIPE_SECRET'));
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'customer_email' => Auth::user()->email,
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => intval($grandAmount * 100),
                            'product_data' => ['name' => 'Order #' . $order->id],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'cancel_url'  => route('cancel', ['order' => $order->id]),
                    'success_url' => route('success', ['order' => $order->id, 'session_id' => '{CHECKOUT_SESSION_ID}']),                    'cancel_url' => route('cancel', ['order' => $order->id]),
                    'metadata' => [ 
                        'order_id' => $order->id,
                    ],
                ]);
                $redirect_url = $session->url;

                // 🧾 Log the wallet transaction (so we can link it to invoice)
                $transaction = $wallet->transactions()->create([
                    'wallet_id'     => $wallet->id,
                    'customer_id'   => $customer->id,
                    'type'          => 'withdrawal',
                    'amount'        => -abs($grandAmount),
                    'status'        => 'pending', // still pending until Stripe confirms
                    'reference'     => 'order_' . $order->id,
                    'description'   => 'Stripe payment for Order #' . $order->id,
                    'metadata'      => [
                        'order_id' => $order->id,
                        'method'   => 'stripe',
                        'stripe_session_id' => $session->id,
                    ],
                ]);

            
                // 🔗 Update the invoice to include this transaction
                $invoice->update([
                    'wallet_transaction_id' => $transaction->id,
                    'invoice_url' => $redirect_url,
                ]);

                $invoice->update(['invoice_url' => $redirect_url]);
                $order->markAsProcessing($redirect_url);   
            }

            elseif ($paymentMethod->slug === 'nowpayments') {
                $wallet = Auth::user()->wallet;
                $customer = Auth::user();

                $paymentController = new PaymentMethodController();
                $payResult = $paymentController->createInvoiceNowPayments($order);

                if ($payResult['status'] === 'success' && isset($payResult['data']['invoice_url'])) {
                    $redirect_url = $payResult['data']['invoice_url'];

                    // Update both order and invoice with the invoice URL
                    $order->update(['payment_invoice_url' => $redirect_url]);

                    // 🧾 Log the wallet transaction (so we can link it to invoice)
                    $transaction = $wallet->transactions()->create([
                        'wallet_id'     => $wallet->id,
                        'customer_id'   => $customer->id,
                        'type'          => 'withdrawal',
                        'amount'        => -abs($grandAmount),
                        'status'        => 'pending', // until webhook confirms
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
                
                    // 🔗 Update the invoice to include this transaction
                    $invoice->update([
                        'wallet_transaction_id' => $transaction->id,
                        'invoice_url' => $redirect_url,
                    ]);

                    // Optional: Dispatch Livewire event for frontend update
                    $this->dispatch('set-invoice-url', ['url' => $redirect_url]);

                    // ⚠️ Don't mark as paid yet — await NowPayments IPN/webhook
                    $order->markAsProcessing($redirect_url);

                } else {
                    Log::error('NowPayments invoice creation failed', [
                        'order_id' => $order->id,
                        'response' => $payResult,
                    ]);
                    throw new \Exception('Failed to create NowPayments invoice.');
                }
            }

            DB::commit();
            CartManagement::clearCartItems();

            Mail::to(Auth::user())->send(new OrderPlaced($order));
            $this->alert('success', 'Order placed successfully!');

            // 4) Redirect
            return redirect($redirect_url
                ?: $invoice->invoice_url
                ?: route('success', ['order' => $order->id])
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed', ['error' => $e->getMessage()]);
            $this->alert('error', 'Error placing order: ' . $e->getMessage());
        }
    }

    /**
     * Process XUI clients for the order.
     *
     * @param Order $order
     * @return void
     * @throws Exception
     */
    // 🔥 This method is called after the order is placed

    protected function processXui(Order $order)
    {
        foreach ($order->items as $item) {
            $plan = $item->serverPlan;
            $xuiService = new XUIService($plan->server_id);
            $inbound_id = $xuiService->getDefaultInboundId();

            for ($i = 0; $i < $item->quantity; $i++) { // 🔥 Loop by quantity
                // ✅ Step 1: Create client remotely
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
                    $remoteInbound = collect($xuiService->getInbounds($plan->server_id))
                        ->firstWhere('id', $inbound_id);

                    if (!$remoteInbound) {
                        throw new \Exception("Remote inbound ID {$inbound_id} not found after client creation.");
                    }

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

            // ✅ Step 2: Fallback (sync all inbounds, retry missing clients)
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

        // ✅ After processing all items
        $order->markAsCompleted();
    }

    public function fetchPaymentMethods()
    {
        $this->payment_methods = PaymentMethod::where('is_active', true)->get();
    }

    public function mount()
    {
        $this->customer = Auth::guard('customer')->user();
        $this->name = $this->customer->name;
        $this->email = $this->customer->email;
        $this->phone = $this->customer->phone;
        $this->telegram_id = $this->customer->telegram_id;

        $this->order_items = CartManagement::getCartItemsFromCookie();
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->payment_methods = PaymentMethod::where('is_active', true)->get();

        if (count($this->order_items) === 0) {
            return redirect('/servers');
        }
    }

    public function render()
    {
        return view('livewire.checkout-page', [
            'order_items' => $this->order_items,
            'grand_amount' => $this->grand_amount,
            'customer' => $this->customer,
            'payment_methods' => $this->payment_methods,
        ]);
    }
}
