<?php

namespace App\Livewire;

use Exception;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Invoice;
use Livewire\Component;
use App\Models\OrderItem;
use Stripe\PaymentIntent;
use App\Models\ServerPlan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Controllers\PaymentMethodController;

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
    public $selectedPaymentMethod = null;

    public function placeOrder()
    {
        // Validate form inputs
        $validatedData = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'selectedPaymentMethod' => 'required|exists:payment_methods,id',
        ]);

        $order_items = CartManagement::getCartItemsFromCookie();

        // Debug: Log the order items
        Log::info('Order items:', $order_items);

        DB::beginTransaction();

        try {
            // Create new order instance
            $order = new Order();
            $order->customer_id = auth()->user()->id;
            $order->grand_amount = CartManagement::calculateGrandTotal($order_items);
            $order->currency = 'usd'; // Adjust currency as needed
            $order->payment_method = $validatedData['selectedPaymentMethod']; // Add this line
            $order->payment_status = 'pending';
            $order->order_status = 'new';
            $order->notes = 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . $order->grand_amount . '$ | Placed at: ' . now();
            $order->save();


            // Log order creation
            Log::info('Order Created:', $order->toArray());

            // Create order items and relate them to the order
            foreach($order_items as $item){
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->server_plan_id = $item['server_plan_id'];
                $orderItem->quantity = $item['quantity'];
                $orderItem->unit_amount = $item['price'];
                $orderItem->total_amount = $item['total_amount'];
                $orderItem->save();

                // Log order item creation
                Log::info('Order Items Created:', $orderItem->toArray());
            }

            // Create new invoice instance
            $invoice = new Invoice();
            $invoice->payment_method_id = $this->selectedPaymentMethod;
            $invoice->order_id = $order->id;
            $invoice->order_description = 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . $order->grand_amount . '$ | Placed at: ' . now();
            $invoice->price_amount = CartManagement::calculateGrandTotal($order_items);
            $invoice->price_currency = 'usd'; // Ensure this is a string
            $invoice->pay_currency = 'usd'; // Ensure this is a string
            $invoice->ipn_callback_url = route('webhook.payment');
            $invoice->invoice_url = route('invoice', ['order' => $order->id]);
            $invoice->success_url = route('success', ['order' => $order->id]);
            $invoice->cancel_url = route('cancel', ['order' => $order->id]);
            $invoice->partially_paid_url = route('cancel', ['order' => $order->id]);
            $invoice->is_fixed_rate = true;
            $invoice->is_fee_paid_by_user = true;
            $invoice->save();

            // Log invoice item creation
            Log::info('Invoice Created:', $invoice->toArray());

            // Handle payment processing based on the selected payment method
            $paymentMethod = PaymentMethod::find($validatedData['selectedPaymentMethod']);
            if ($paymentMethod->name === 'stripe') {
                // Stripe payment logic
                Stripe::setApiKey(env('STRIPE_SECRET'));

                $line_items = [];
                foreach ($order_items as $item) {
                    $serverPlan = ServerPlan::find($item['server_plan_id']);
                    $line_items[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $item['unit_amount'],
                            'product_data' => [
                                'name' => $serverPlan->name,
                            ],
                        ],
                        'quantity' => $item['quantity'],
                    ];
                }

                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $line_items,
                    'mode' => 'payment',
                    'success_url' => route('success', ['order' => $order->id]),
                    'cancel_url' => route('cancel', ['order' => $order->id]),
                ]);

                $order->payment_status = 'pending';
            } elseif ($paymentMethod->name === 'nowpayments') {
                // NowPayments payment logic
                $paymentController = new PaymentMethodController();
                $invoice = $paymentController->createInvoice($order->grand_amount, $order->currency, $this->email);

                if ($invoice && $invoice->success_url) {
                    $order->payment_status = 'pending';
                    $invoice->order_id = $order->id;
                } else {
                    $order->payment_status = 'failed';
                    throw new \Exception('Payment failed.');
                }
            }

            $order->save();

            DB::commit();

            // Clear the cart after order placement
            CartManagement::clearCartItems();
            $this->order_items = [];

            // Redirect to a success page or show a success message
            session()->flash('success', 'Order placed successfully!');
            return redirect()->route('success');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Order placement failed: ' . $e->getMessage());
        }
    }


    private function createNowpaymentsInvoice($order_id, $grand_amount)
    {
        $paymentController = new PaymentMethodController();
        $data = [
            'price_amount' => $grand_amount,
            'price_currency' => 'usd',
            'order_id' => $order_id,
            'order_description' => 'Order #' . $order_id,
            'ipn_callback_url' => route('webhook.payment'),
            'success_url' => route('success', ['order' => $order_id]),
            'cancel_url' => route('cancel', ['order' => $order_id]),
            'partially_paid_url' => route('cancel', ['order' => $order_id]),
            'is_fixed_rate' => true,
            'is_fee_paid_by_user' => true,
        ];

        return $paymentController->createInvoice(new Request($data));
    }

    private function createStripeCheckoutSession($order)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $order->orderItems->map(function ($item) {
                    return [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $item->serverPlan->name,
                            ],
                            'unit_amount' => $item->serverPlan->price,
                        ],
                        'quantity' => $item->quantity,
                    ];
                })->toArray(),
                'mode' => 'payment',
                'success_url' => route('success'),
                'cancel_url' => route('cancel'),
            ]);

            return $session->url;
        } catch (Exception $e) {
            Log::error('Error creating Stripe Checkout session: ' . $e->getMessage());
            $this->alert('error', 'Failed to initiate payment. Please try again later.');
            return null;
        }
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
        $this->payment_methods = PaymentMethod::all();
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