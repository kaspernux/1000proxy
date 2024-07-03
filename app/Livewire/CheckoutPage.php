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
    public $payment_methods = [];
    public $selectedPaymentMethod;

    public function placeOrder()
{
    // Ensure you have the correct PaymentMethod instance for the selected ID
    $selectedPaymentMethod = PaymentMethod::find($this->selectedPaymentMethod);

    // Validate form inputs
    $validatedData = $this->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'telegram_id' => 'nullable|string|max:255',
        'selectedPaymentMethod' => 'required|exists:payment_methods,id',
    ]);

    $order_items = CartManagement::getCartItemsFromCookie();
    $line_items = [];
    $data = [];
    $redirect_url = '';

    DB::beginTransaction();

    try {
        // Handle payment processing based on the selected payment method
        $paymentMethod = PaymentMethod::find($validatedData['selectedPaymentMethod']);

        // Create new order instance
        $order = new Order();
        $order->customer_id = auth()->user()->id;
        $order->grand_amount = CartManagement::calculateGrandTotal($order_items);
        $order->currency = 'usd'; // Adjust currency as needed
        $order->payment_method = $validatedData['selectedPaymentMethod'];
        $order->order_status = 'new';
        $order->notes = 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . $order->grand_amount . '$ | Placed at: ' . now();

        // Save the order before proceeding to save the order items
        $order->save();

        // Create order items and relate them to the order
        foreach ($order_items as $item) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->server_plan_id = $item['server_plan_id'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->unit_amount = $item['price'];
            $orderItem->total_amount = $item['total_amount'];
            $orderItem->save();

            // Log order item creation
            Log::info('Order Item Created:', $orderItem->toArray());

            if ($paymentMethod->id == 2) {
                // Stripe payment logic
                Stripe::setApiKey(env('STRIPE_SECRET'));

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
            } elseif ($paymentMethod->id == 3) {
                // NowPayments payment logic
                $paymentController = new PaymentMethodController();
                $invoice = $paymentController->createInvoiceNowPayments($order);

                if ($invoice['status'] === 'success' && isset($invoice['data']['invoice_url'])) {
                    $invoiceUrl = $invoice['data']['invoice_url'];
                    $redirect_url = $invoiceUrl;
                } else {
                    throw new Exception('Failed to create invoice: ' . $invoice['message']);
                }
            }
        }

        if ($paymentMethod->id == 2) {
            $sessionCheckout = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => auth()->user()->email,
                'line_items' => $line_items,
                'mode' => 'payment',
                'success_url' => route('success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('cancel'),
            ]);

            $redirect_url = $sessionCheckout->url;
        }

        // Create new invoice instance
        $invoice = new Invoice();
        $invoice->payment_method_id = $paymentMethod->id;
        $invoice->order_id = $order->id;
        $invoice->order_description = 'Order placed by: ' . auth()->user()->name . ' (ID #: ' . auth()->user()->id . ') | Total amount: ' . $order->grand_amount . '$ | Placed at: ' . now();
        $invoice->price_amount = intval(CartManagement::calculateGrandTotal($order_items)); // Convert to cents
        $invoice->price_currency = 'usd'; // Ensure this is a string
        $invoice->pay_currency = $order->currency; // Ensure this is a string
        $invoice->ipn_callback_url = route('webhook.nowpay');
        $invoice->invoice_url = $redirect_url;
        $invoice->success_url = route('success', ['order' => $order->id]);
        $invoice->cancel_url = route('cancel', ['order' => $order->id]);
        $invoice->partially_paid_url = route('cancel');
        $invoice->is_fixed_rate = true;
        $invoice->is_fee_paid_by_user = true;
        $invoice->save();

        // Log order and invoice creation
        Log::info('Order Created:', $order->toArray());
        Log::info('Invoice Created:', $invoice->toArray());

        // Commit the transaction before redirecting
        DB::commit();

        // Clear the cart after order placement
        CartManagement::clearCartItems();
        $this->order_items = [];

        // Redirect to the appropriate URL based on payment method
        return redirect($redirect_url);

    } catch (\Exception $e) {
        DB::rollBack();
        session()->flash('error', 'Order placement failed: ' . $e->getMessage());
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
