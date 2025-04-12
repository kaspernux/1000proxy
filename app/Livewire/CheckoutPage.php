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
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PaymentController;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Controllers\PaymentMethodController;
use App\Services\XUIService;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use Illuminate\Support\Str;


#[Title('Checkout - 1000 PROXIES')]
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
    public $selectedPaymentMethod;

    public function placeOrder()
    {
        $validatedData = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'selectedPaymentMethod' => 'required|exists:payment_methods,id',
        ]);

        DB::beginTransaction();

        try {
            $order_items = CartManagement::getCartItemsFromCookie();
            $line_items = [];
            $paymentMethod = PaymentMethod::find($validatedData['selectedPaymentMethod']);

            $order = Order::create([
                'customer_id' => auth()->user()->id,
                'grand_amount' => CartManagement::calculateGrandTotal($order_items),
                'currency' => 'usd',
                'payment_method' => $validatedData['selectedPaymentMethod'],
                'order_status' => 'new',
                'notes' => 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . CartManagement::calculateGrandTotal($order_items) . '$ | Placed at: ' . now(),
            ]);

            foreach ($order_items as $item) {
                $serverPlan = ServerPlan::findOrFail($item['server_plan_id']);

                $order->items()->create([
                    'server_plan_id' => $item['server_plan_id'],
                    'quantity' => $item['quantity'],
                    'unit_amount' => $serverPlan->price,
                    'total_amount' => $serverPlan->price * $item['quantity'],
                ]);

                $xuiService = new XUIService($serverPlan->server_id);
                $inbound_id = $xuiService->getDefaultInboundId();

                $response = $xuiService->addInboundAccount(
                    $serverPlan->server_id,
                    $xuiService->generateUID(),
                    $inbound_id,
                    now()->addDays($serverPlan->days)->timestamp * 1000,
                    'Order#' . $order->id . ' - Client ID ' . $order->customer_id,
                    $serverPlan->volume
                );

                if (!$response || isset($response['error'])) {
                    throw new \Exception("Inbound creation failed: " . json_encode($response));
                }

                // Sync all clients from the remote server
                $remoteInbounds = $xuiService->getInbounds($serverPlan->server_id);
                foreach ($remoteInbounds as $inbound) {
                    $localInbound = ServerInbound::updateOrCreate([
                        'server_id' => $serverPlan->server_id,
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

                    $parsedSettings = is_string($inbound->settings)
                        ? json_decode($inbound->settings, true)
                        : (array) $inbound->settings;

                    $clients = $parsedSettings['clients'] ?? [];

                    foreach ($clients as $client) {
                        $client = (array) $client;
                        $expectedEmail = 'Order#' . $order->id . ' - Client ID ' . $order->customer_id;

                        if (($client['email'] ?? null) === $expectedEmail) {
                            $clientModel = ServerClient::fromRemoteClient($client, $localInbound->id, $client['subscription_link'] ?? null);
                            $clientModel->update(['plan_id' => $serverPlan->id]);
                        }
                    }

                }

                $line_items[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => intval($serverPlan->price * 100),
                        'product_data' => ['name' => $serverPlan->name],
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            $redirect_url = '';

            if ($paymentMethod->slug === 'stripe') {
                Stripe::setApiKey(env('STRIPE_SECRET'));
                $sessionCheckout = Session::create([
                    'payment_method_types' => ['card'],
                    'customer_email' => auth()->user()->email,
                    'line_items' => $line_items,
                    'mode' => 'payment',
                    'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('cancel'),
                ]);
                $redirect_url = $sessionCheckout->url;

            } elseif ($paymentMethod->slug === 'nowpayments') {
                $paymentController = new PaymentMethodController();
                $payResult = $paymentController->createInvoiceNowPayments($order);

                if ($payResult['status'] === 'success' && isset($payResult['data']['invoice_url'])) {
                    $redirect_url = $payResult['data']['invoice_url'];
                    $this->dispatch('set-invoice-url', ['url' => $redirect_url]);
                } else {
                    Log::error('NowPayments invoice creation failed:', ['response' => $payResult]);
                    throw new \Exception('Failed to create invoice. Try another payment method.');
                }

                Invoice::create([
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethod->id,
                    'price_amount' => $order->grand_amount,
                    'price_currency' => 'usd',
                    'pay_amount' => $order->grand_amount,
                    'pay_currency' => 'xmr',
                    'order_description' => $order->notes,
                    'ipn_callback_url' => env('APP_URL') . '/webhook',
                    'invoice_url' => $redirect_url,
                    'success_url' => route('success', ['order' => $order->id]),
                    'cancel_url' => route('cancel', ['order' => $order->id]),
                    'partially_paid_url' => route('cancel', ['order' => $order->id]),
                    'is_fixed_rate' => true,
                    'is_fee_paid_by_user' => true,
                ]);

            } elseif ($paymentMethod->slug === 'wallet') {
                $customer = Auth::user();
                if ($customer->wallet >= $order->grand_amount) {
                    $customer->wallet -= $order->grand_amount;
                    $customer->save();
                    $order->order_status = 'completed';
                    $order->save();
                    $redirect_url = route('success', ['order' => $order->id]);
                } else {
                    throw new \Exception('Insufficient balance in wallet. Please add funds.');
                }
            }

            DB::commit();
            CartManagement::clearCartItems();

            try {
                Mail::to(auth()->user()->email)->send(new OrderPlaced($order));
            } catch (\Exception $mailException) {
                Log::error('Mail sending failed:', ['error' => $mailException->getMessage()]);
            }

            $this->alert('success', 'Order placed successfully!');
            return redirect($redirect_url);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed:', ['error' => $e->getMessage()]);
            $this->alert('warning', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function fetchPaymentMethods()
    {
        $this->payment_methods = PaymentMethodController::getPaymentMethods();
    }

    public function mount()
    {
        // Initialize customer details from Auth
        $this->customer = Auth::guard('customer')->user();
        $this->name = $this->customer->name;
        $this->email = $this->customer->email;
        $this->phone = $this->customer->phone;
        $this->telegram_id = $this->customer->telegram_id;

        // Initialize order items and grand amount from Cart
        $this->order_items = CartManagement::getCartItemsFromCookie();
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);

        // Initialize payment methods
        $this->fetchPaymentMethods();

        if (count($this->order_items) == 0) {
            $order_items = $this->order_items;
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