<?php

namespace App\Livewire;

use Exception;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Invoice;
use Livewire\Component;
use App\Models\OrderItem;
use Stripe\Checkout\Session;
use App\Models\ServerPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Controllers\PaymentMethodController;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;

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
    public $payment_methods;


    public function placeOrder()
    {
        // Ensure you have the correct PaymentMethod instance for the selected ID
        $selectedPaymentMethod = PaymentMethod::find($this->payment_methods);

        // Validate form inputs
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'payment_methods' => 'required|exists:payment_methods,id',
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
            $order->payment_method = $this->payment_methods->name;
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
            $invoice->payment_method_id = $this->payment_methods->id;
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
            $paymentMethod = PaymentMethod::find($selectedPaymentMethod);
            if ($selectedPaymentMethod->name === 'stripe') {
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

                // Save the session ID to use later
                $order->payment_status = 'pending';
                $order->save();

                // Commit the transaction before redirecting
                DB::commit();

                // Redirect to Stripe checkout session
                return redirect($session->url);

            } elseif ($selectedPaymentMethod->name === 'nowpayments') {
                dd('Testing Wallet Payment...');

                /* // NowPayments payment logic
                $paymentController = new PaymentMethodController();
                $invoice = $paymentController->createInvoice($order->grand_amount, $order->currency, $this->email);

                if ($invoice && $invoice->success_url) {
                    $order->payment_status = 'pending';
                    $invoice->order_id = $order->id;
                    $invoice->save();

                    // Commit the transaction before redirecting
                    DB::commit();

                    // Redirect to NowPayments invoice URL
                    return redirect($session->url);
                } else {
                    $order->payment_status = 'failed';
                    throw new \Exception('Payment failed.');
                } */
            }

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
