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
        // Validate form inputs
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

            // Initialize payment method
            $paymentMethod = PaymentMethod::find($validatedData['selectedPaymentMethod']);

            // Create new order instance
            $order = new Order();
            $order->customer_id = auth()->user()->id;
            $order->grand_amount = CartManagement::calculateGrandTotal($order_items);
            $order->currency = 'usd'; // Adjust currency as needed
            $order->payment_method = $validatedData['selectedPaymentMethod'];
            $order->order_status = 'new';
            $order->notes = 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . $order->grand_amount . '$ | Placed at: ' . now();

            // Save the order before proceeding
            $order->save();

            // Create new invoice instance
            $invoice = Invoice::create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'payment_id' => session('paymentId'), // Assuming paymentId is stored in session
                'iid' => session('iid'), // Assuming iid is stored in session
                'payment_status' => session('payment_status'), // Adjust as per your session data
                'pay_address' => session('pay_address'), // Adjust as per your session data
                'price_amount' => CartManagement::calculateGrandTotal($order_items),
                'price_currency' => 'usd',
                'pay_amount' => CartManagement::calculateGrandTotal($order_items),
                'pay_currency' => 'xmr', // Adjust as per your session data
                'order_description' => 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . $order->grand_amount . '$ | Placed at: ' . now(),
                'ipn_callback_url' => 'https://1000proxybot/webhook',
                'invoice_url' => "https://nowpayments.io/payment?iid=" . session('iid') . "&paymentId=" . session('paymentId'), // Correctly interpolate iid and paymentId
                'success_url' => route('success', ['order' => $order->id]),
                'cancel_url' => route('cancel', ['order' => $order->id]),
                'partially_paid_url' => route('cancel', ['order' => $order->id]),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create order items and relate them to the order
            foreach ($order_items as $item) {
                $line_items[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => intval($item['price'] * 100), // Convert to cents
                        'product_data' => [
                            'name' => $item['name'],
                        ],
                    ],
                    'quantity' => $item['quantity'],
                ];

                $data[] = [
                    'server_plan_id' => $item['server_plan_id'],
                    'quantity' => $item['quantity'],
                    'unit_amount' => $item['price'],
                    'total_amount' => $item['price'] * $item['quantity'],
                    'order_id' => $order->id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }

            // Save the order items
            $order->items()->createMany($data);

            // Log order and invoice creation
            Log::info('Order Created:', $order->toArray());
            Log::info('Order Items Created:', $order_items);
            Log::info('Invoice Created:', $invoice->toArray());

            // Create Stripe or NowPayments session based on selected payment method
            $redirect_url = '';

            if ($paymentMethod->id == 2) {
                // Stripe payment logic
                Stripe::setApiKey(env('STRIPE_SECRET'));

                // Create Stripe session
                $sessionCheckout = Session::create([
                    'payment_method_types' => ['card'],
                    'customer_email' => auth()->user()->email,
                    'line_items' => $line_items,
                    'mode' => 'payment',
                    'success_url' => route('success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('cancel'),
                ]);

                $redirect_url = $sessionCheckout->url;
            } elseif ($paymentMethod->id == 3) {
                // NowPayments payment logic
                $paymentController = new PaymentController();

                // Create the payment
                $payOrder = $paymentController->createInvoice($order);

                // Check if the response is successful
                if ($payOrder->status() === 200) {
                    // Decode the JSON response body
                    $invoiceResponse = json_decode($payOrder->getContent(), true);

                    // Check if the necessary keys exist in the response
                    if (isset($invoiceResponse['invoice_url'])) {
                        $redirect_url = $invoiceResponse['invoice_url'];
                        $this->dispatch('set-invoice-url', ['url' => $invoiceResponse['invoice_url']]);
                    } else {
                        $this->alert('warning', 'Failed to create invoice. Unknown error');
                        return;
                    }
                } else {
                    $this->alert('warning', 'Failed to create invoice. Try another payment method.');
                    return;
                }
            } elseif ($paymentMethod->id == 1) {
                // Wallet payment logic
                $customer = Auth::user();
                Log::info('Customer wallet balance:', ['balance' => $customer->wallet]);
                Log::info('Order grand amount:', ['amount' => $order->grand_amount]);

                if ($customer->wallet >= $order->grand_amount) {
                    $customer->wallet -= $order->grand_amount;
                    $customer->save();
                    // Log wallet deduction
                    Log::info('Wallet deduction:', [
                        'customer_id' => $customer->id,
                        'amount_deducted' => $order->grand_amount,
                        'remaining_balance' => $customer->wallet,
                    ]);

                    // Update order status and payment status
                    $order->order_status = 'completed';
                    $order->save();

                    // No need for redirect URL as the payment is completed instantly
                    $redirect_url = route('success', ['order' => $order->id]);
                } else {
                    $this->alert('warning', 'Insufficient balance in wallet. Please add funds.');
                    return;
                }
            }

            // Commit the transaction before redirecting
            DB::commit();

            // Clear the cart after order placement
            CartManagement::clearCartItems();

            // Send Notifications/Email after Payment
            Mail::to(request()->user())->send(new OrderPlaced($order));

            $this->alert('success', 'Order placed successfully!');

            // Redirect to the appropriate URL based on payment method
            return redirect($redirect_url);
        } catch (Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();

            // Log the error for debugging
            Log::error('Order placement failed:', ['error' => $e->getMessage()]);

            // Handle other exceptions
            $this->alert('warning', 'An error occurred while processing your order. Please try again.');
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