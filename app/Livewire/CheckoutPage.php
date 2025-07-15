<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Helpers\CartManagement;
use App\Services\PaymentGateways\StripePaymentService;
use App\Services\PaymentGateways\PayPalPaymentService;
use App\Services\PaymentGateways\CryptoPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Checkout - Complete Your Order | 1000 PROXIES')]
class CheckoutPage extends Component
{
    use LivewireAlert;

    // Checkout steps
    public $currentStep = 1; // 1: Cart Review, 2: Billing Info, 3: Payment, 4: Confirmation
    public $totalSteps = 4;

    // Cart data
    public $cart_items = [];
    public $order_summary = [];

    // Billing information
    #[Rule('required|string|max:255')]
    public $first_name = '';

    #[Rule('required|string|max:255')]
    public $last_name = '';

    #[Rule('required|email|max:255')]
    public $email = '';

    #[Rule('required|string|max:20')]
    public $phone = '';

    #[Rule('required|string|max:255')]
    public $company = '';

    #[Rule('required|string|max:255')]
    public $address = '';

    #[Rule('required|string|max:255')]
    public $city = '';

    #[Rule('required|string|max:10')]
    public $postal_code = '';

    #[Rule('required|string|max:255')]
    public $country = '';

    // Payment information
    public $payment_method = 'wallet';
    public $save_payment_method = false;
    public $agree_to_terms = false;
    public $subscribe_newsletter = true;

    // Processing states
    public $is_processing = false;
    public $order_created = null;
    public $payment_intent = null;
    public $error_message = '';

    // Coupon system
    public $coupon_code = '';
    public $applied_coupon = null;
    public $discount_amount = 0;

    protected $listeners = [
        'cartUpdated' => 'refreshCart',
        'paymentCompleted' => 'handlePaymentCompleted',
        'paymentFailed' => 'handlePaymentFailed'
    ];

    public function mount()
    {
        // Redirect if cart is empty
        $this->cart_items = CartManagement::getCartItemsFromCookie();

        if (empty($this->cart_items)) {
            session()->flash('warning', 'Your cart is empty. Please add items before checkout.');
            return redirect()->route('products');
        }

        $this->calculateOrderSummary();
        $this->prefillUserData();
    }

    #[Computed]
    public function stepProgress()
    {
        return ($this->currentStep / $this->totalSteps) * 100;
    }

    private function prefillUserData()
    {
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            $this->first_name = $customer->first_name ?? '';
            $this->last_name = $customer->last_name ?? '';
            $this->email = $customer->email ?? '';
            $this->phone = $customer->phone ?? '';
        }
    }

    private function calculateOrderSummary()
    {
        $subtotal = CartManagement::calculateGrandTotal($this->cart_items);
        $tax = $this->calculateTax($subtotal);
        $shipping = $this->calculateShipping($subtotal);

        $this->order_summary = [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'discount' => $this->discount_amount,
            'total' => $subtotal + $tax + $shipping - $this->discount_amount,
            'items_count' => count($this->cart_items)
        ];
    }

    private function calculateTax($subtotal)
    {
        // Calculate based on country/region
        $taxRates = [
            'US' => 0.08,
            'CA' => 0.13,
            'GB' => 0.20,
            'EU' => 0.21,
        ];

        $rate = $taxRates[$this->country] ?? 0;
        return round($subtotal * $rate, 2);
    }

    private function calculateShipping($subtotal)
    {
        // Free shipping over $50
        if ($subtotal >= 50) {
            return 0;
        }

        // Regional shipping rates
        $shippingRates = [
            'US' => 5.99,
            'CA' => 7.99,
            'EU' => 9.99,
            'default' => 12.99
        ];

        return $shippingRates[$this->country] ?? $shippingRates['default'];
    }

    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {

            // Validate current step before proceeding
            if ($this->validateCurrentStep()) {
                $this->currentStep++;

                // Perform step-specific actions
                $this->handleStepTransition();
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    private function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1: // Cart Review
                return !empty($this->cart_items);

            case 2: // Billing Info
                return $this->validateBillingInfo();

            case 3: // Payment
                return $this->agree_to_terms;

            default:
                return true;
        }
    }

    private function validateBillingInfo()
    {
        try {
            $this->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'country' => 'required|string|max:255',
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function handleStepTransition()
    {
        switch ($this->currentStep) {
            case 2:
                // Calculate shipping and tax based on address
                $this->calculateOrderSummary();
                break;

            case 3:
                // Prepare payment processing
                $this->preparePayment();
                break;

            case 4:
                // Create order and process payment
                $this->processOrder();
                break;
        }
    }

    private function preparePayment()
    {
        // Initialize payment gateways based on selected method
        switch ($this->payment_method) {
            case 'stripe':
                // Initialize Stripe
                break;
            case 'paypal':
                // Initialize PayPal
                break;
            case 'crypto':
                // Initialize crypto payment
                break;
        }
    }

    public function applyCoupon()
    {
        if (empty($this->coupon_code)) {
            $this->alert('error', 'Please enter a coupon code');
            return;
        }

        // Validate coupon
        $discount = $this->validateCoupon($this->coupon_code);

        if ($discount > 0) {
            $this->discount_amount = $discount;
            $this->applied_coupon = $this->coupon_code;
            $this->calculateOrderSummary();

            $this->alert('success', "Coupon applied! You saved $" . number_format($discount, 2), [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        } else {
            $this->alert('error', 'Invalid or expired coupon code', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    private function validateCoupon($code)
    {
        // Mock coupon validation - replace with real system
        $coupons = [
            'SAVE10' => 0.10,
            'WELCOME' => 5.00,
            'FIRST20' => 0.20,
        ];

        if (isset($coupons[$code])) {
            $discount = $coupons[$code];

            if ($discount < 1) {
                return round($this->order_summary['subtotal'] * $discount, 2);
            } else {
                return min($discount, $this->order_summary['subtotal']);
            }
        }

        return 0;
    }

    public function removeCoupon()
    {
        $this->discount_amount = 0;
        $this->applied_coupon = null;
        $this->coupon_code = '';
        $this->calculateOrderSummary();

        $this->alert('info', 'Coupon removed');
    }

    private function processOrder()
    {
        if ($this->is_processing) {
            return;
        }

        $this->is_processing = true;

        try {
            DB::beginTransaction();

            // Create order
            $order = $this->createOrder();
            $this->order_created = $order;

            // Process payment
            $paymentResult = $this->processPayment($order);

            if ($paymentResult['success']) {
                // Update order status
                $order->update([
                    'status' => 'paid',
                    'payment_status' => 'completed',
                    'payment_method' => $this->payment_method,
                    'payment_transaction_id' => $paymentResult['transaction_id'] ?? null,
                ]);

                // Clear cart
                CartManagement::clearCartItems();

                // Provision services
                $this->provisionServices($order);

                DB::commit();

                $this->currentStep = 4; // Confirmation step

                $this->alert('success', 'Order completed successfully!', [
                    'position' => 'center',
                    'timer' => false,
                    'toast' => false,
                ]);

            } else {
                throw new \Exception($paymentResult['error'] ?? 'Payment failed');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error_message = $e->getMessage();

            $this->alert('error', 'Order processing failed: ' . $e->getMessage(), [
                'position' => 'top-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        } finally {
            $this->is_processing = false;
        }
    }

    private function createOrder()
    {
        $orderData = [
            'customer_id' => Auth::guard('customer')->id(),
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $this->payment_method,
            'subtotal' => $this->order_summary['subtotal'],
            'tax_amount' => $this->order_summary['tax'],
            'shipping_amount' => $this->order_summary['shipping'],
            'discount_amount' => $this->order_summary['discount'],
            'total_amount' => $this->order_summary['total'],
            'currency' => 'USD',
            'billing_first_name' => $this->first_name,
            'billing_last_name' => $this->last_name,
            'billing_email' => $this->email,
            'billing_phone' => $this->phone,
            'billing_company' => $this->company,
            'billing_address' => $this->address,
            'billing_city' => $this->city,
            'billing_postal_code' => $this->postal_code,
            'billing_country' => $this->country,
            'coupon_code' => $this->applied_coupon,
        ];

        $order = Order::create($orderData);

        // Create order items
        foreach ($this->cart_items as $item) {
            $order->orderItems()->create([
                'server_plan_id' => $item['server_plan_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
            ]);
        }

        return $order;
    }

    private function processPayment($order)
    {
        try {
            switch ($this->payment_method) {
                case 'wallet':
                    return $this->processWalletPayment($order);
                case 'stripe':
                    return $this->processStripePayment($order);
                case 'paypal':
                    return $this->processPayPalPayment($order);
                case 'crypto':
                    return $this->processCryptoPayment($order);
                default:
                    throw new \Exception('Invalid payment method');
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function processWalletPayment($order)
    {
        $customer = Auth::guard('customer')->user();

        if ($customer->wallet_balance < $order->total_amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        // Deduct from wallet
        $customer->decrement('wallet_balance', $order->total_amount);

        // Create transaction record
        $customer->transactions()->create([
            'type' => 'debit',
            'amount' => $order->total_amount,
            'description' => "Order payment: {$order->order_number}",
            'reference' => $order->order_number,
        ]);

        return ['success' => true, 'transaction_id' => 'WALLET-' . uniqid()];
    }

    private function processStripePayment($order)
    {
        // Implement Stripe payment processing
        $stripeService = new StripePaymentService();
        return $stripeService->processPayment($order);
    }

    private function processPayPalPayment($order)
    {
        // Implement PayPal payment processing
        $paypalService = new PayPalPaymentService();
        return $paypalService->processPayment($order);
    }

    private function processCryptoPayment($order)
    {
        // Implement crypto payment processing
        $cryptoService = new CryptoPaymentService();
        return $cryptoService->processPayment($order);
    }

    private function provisionServices($order)
    {
        // Provision VPN/Proxy services for the order
        foreach ($order->orderItems as $item) {
            // This would integrate with XUI service to create client configurations
            // For now, just update the item status
            $item->update(['status' => 'active']);
        }
    }

    #[On('cartUpdated')]
    public function refreshCart()
    {
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        $this->calculateOrderSummary();
    }

    #[On('paymentCompleted')]
    public function handlePaymentCompleted($data)
    {
        $this->currentStep = 4;
        $this->alert('success', 'Payment completed successfully!');
    }

    #[On('paymentFailed')]
    public function handlePaymentFailed($error)
    {
        $this->error_message = $error;
        $this->alert('error', 'Payment failed: ' . $error);
    }

    public function render()
    {
        return view('livewire.checkout-page', [
            'stepProgress' => $this->stepProgress,
            'orderSummary' => $this->order_summary,
        ]);
    }
}
