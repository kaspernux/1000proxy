<?php

namespace App\Livewire;

use Exception;
use Stripe\Stripe;
use App\Models\Order;
use Livewire\Component;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;
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
    public $paymentMethods;
    public $selectedPaymentMethod;
    public $order;

    public function mount($order_id)
    {
        // Load order details based on $order_id
        $this->order = Order::findOrFail($order_id);

        // Initialize customer details from Auth
        $this->customer = Auth::guard('customer')->user();
        $this->name = $this->customer->name;
        $this->email = $this->customer->email;
        $this->phone = $this->customer->phone;
        $this->telegram_id = $this->customer->telegram_id;

        // Retrieve order items from the order
        $this->order_items = $this-items;

        // Calculate grand total for the order
        $this->grand_amount = $this->order->grand_amount;

        // Load payment methods (you need to fetch this based on your implementation)
        $this->paymentMethods = PaymentMethodController::getPaymentMethods(); // Replace with your actual method to fetch payment methods
    }

    public function placeOrder()
    {
        // Validate form inputs
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram_id' => 'nullable|string|max:255',
            'selectedPaymentMethod' => 'required',
        ]);

        foreach ($order_items as $item) {
            $serverPlan = ServerPlan::find($item['server_plan_id']);
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->server_plan_id = $item['server_plan_id'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->unit_amount = $serverPlan->price;
            $orderItem->total_amount = $item['quantity'] * $serverPlan->price;
            $orderItem->agent_bought = 0; // or any logic for agent_bought
            $orderItem->save();

            // Prepare data payload
            $data = [
                'price_amount' => $grand_amount,
                'price_currency' => 'usd',
                'order_id' => $order_id,
                'order_description' => 'Order #' . $order_id,
                'ipn_callback_url' => route('webhook.payment'),
                'success_url' => route('success', ['order' => $order_id]),
                'cancel_url' => route('cancel', ['order' => $order_id]),
                'is_fixed_rate' => true,
                'is_fee_paid_by_user' => true,
            ];

            $line_items[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $serverPlan->price * 100,
                    'product_data' => [
                        'name' => $serverPlan->name,
                    ]
                ],
                'quantity' => $item['quantity'],
            ];

            Log::info('Order Item Created:', $orderItem->toArray()); // Log each order item creation
        }


        // Handle payment method-specific logic
        switch ($this->selectedPaymentMethod) {
            case 'Nowpayments':
                // Call Nowpayments createInvoice method from PaymentMethodController
                $invoiceData = $this->createNowpaymentsInvoice($this->order->id, $this->grand_amount);
                // Handle response or error
                if ($invoiceData) {
                    // Proceed with further steps like redirect or display success message
                    $this->redirect('/success'); // Replace with your actual thank-you page URL
                } else {
                    // Handle error scenario
                    Log::error('Failed to create Nowpayments invoice.');
                    $this->alert('error', 'Failed to create Nowpayments invoice.');
                }
                break;
            case 'Stripe':
                // Call Stripe createInvoice method from PaymentMethodController
                $sessionUrl = $this->createStripeCheckoutSession($this->order);
                // Redirect to Stripe Checkout page
                if ($sessionUrl) {
                    return redirect($sessionUrl);
                } else {
                    // Handle error scenario
                    Log::error('Failed to initiate Stripe Checkout session.');
                    $this->alert('error', 'Failed to initiate payment. Please try again later.');
                }
                break;
            case 'PayPal':
                dd('Testing PayPal...');
                break;
            case 'Bitcoin':
                dd('Testing Bitcoin...');
                break;
            default:
                // Default case, handle unknown or unsupported payment methods
                Log::error('Unsupported payment method selected.');
                return;
        }
    }

    // Method to create Nowpayments invoice using PaymentMethodController
    private function createNowpaymentsInvoice($order_id, $grand_amount)
    {
        // Set the API endpoint
        $url = 'https://api.nowpayments.io/v1/invoice';

        // Prepare data payload
        $data = [
            'price_amount' => $grand_amount,
            'price_currency' => 'usd',
            'order_id' => $order_id,
            'order_description' => 'Order #' . $order_id,
            'ipn_callback_url' => route('webhook.payment'),
            'success_url' => route('success', ['order' => $order_id]),
            'cancel_url' => route('cancel', ['order' => $order_id]),
            'is_fixed_rate' => true,
            'is_fee_paid_by_user' => true,
        ];

        // Encode data as JSON
        $payload = json_encode($data);

        try {
            // Initialize cURL session
            $curl = curl_init();

            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    // Add any necessary headers here, like API keys or tokens
                ],
            ]);

            // Execute cURL request
            $response = curl_exec($curl);

            // Check for cURL errors
            if (curl_errno($curl)) {
                $error_message = curl_error($curl);
                throw new \Exception('cURL error: ' . $error_message);
            }

            // Close cURL session
            curl_close($curl);

            // Decode JSON response
            $invoiceData = json_decode($response, true);

            // Log the invoice creation
            Log::info('Nowpayments Invoice Created:', $invoiceData);

            // Return the invoice data
            return $invoiceData;

        } catch (\Exception $e) {
            // Handle error scenario
            Log::error('Failed to create Nowpayments invoice: ' . $e->getMessage());
            $this->alert('error', 'Failed to create payment invoice. Please try again later.');
            return null;
        }
    }

    // Method to create Stripe Checkout session
    private function createStripeCheckoutSession($order)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Replace with your actual logic to create a Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Order #' . $order->id,
                            ],
                            'unit_amount' => $order->grand_amount * 100, // Amount in cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('success', ['order' => $order->id]),
                'cancel_url' => route('cancel', ['order' => $order->id]),
            ]);

            // Return the Stripe Checkout session URL
            return $session->url;
        } catch (Exception $e) {
            Log::error('Error creating Stripe Checkout session: ' . $e->getMessage());
            $this->alert('error', 'Failed to initiate payment. Please try again later.');
            return null;
        }
    }

    public function render()
    {
        return view('livewire.checkout-page', [
            'order_items' => $this->order_items,
            'grand_amount' => $this->grand_amount,
            'customer' => $this->customer,
            'paymentMethods' => $this->paymentMethods,
        ]);
    }
}