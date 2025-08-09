<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Helpers\CartManagement;
use App\Services\PaymentGateways\StripePaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Checkout - Complete Your Order | 1000 PROXIES')]
class CheckoutPage extends Component
{
    use LivewireAlert;

    // Checkout steps
    public $currentStep = 1; // 1: Cart Review, 2: Billing Info, 3: Payment, 4: Confirmation
    public $totalSteps = 4;

    // Loading states
    public $is_loading = false;
    public $is_processing = false;
    public $is_applying_coupon = false;

    // Cart data
    public $cart_items = [];
    public $order_summary = [];

    // Billing information
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $company = '';
    public $address = '';
    public $city = '';
    public $state = '';
    public $postal_code = '';
    public $country = '';

    // Payment information
    public $payment_method = '';
    public $crypto_currency = '';
    public $save_payment_method = false;
    public $agree_to_terms = false;
    public $subscribe_newsletter = true;

    // Processing states
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

    protected function rules()
    {
        return [
            'first_name' => 'required|string|max:255|min:2',
            'last_name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'country' => 'required|string|max:255',
            'agree_to_terms' => 'accepted',
            'coupon_code' => 'nullable|string|max:50',
            'payment_method' => 'required|in:crypto,stripe,wallet,mir',
            'crypto_currency' => 'required_if:payment_method,crypto|nullable|string|in:btc,eth,xmr,ltc,doge,ada,dot,sol',
        ];
    }

    public function mount()
    {
        // Check authentication using customer guard
        if (!\Illuminate\Support\Facades\Auth::guard('customer')->check()) {
            return redirect('/login');
        }

        // Redirect if cart is empty
        $this->cart_items = CartManagement::getCartItemsFromCookie();

        if (empty($this->cart_items)) {
            session()->flash('warning', 'Your cart is empty. Please add items before checkout.');
            return redirect()->route('servers.index');
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
        if (\Illuminate\Support\Facades\Auth::guard('customer')->check()) {
            $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
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
        $this->is_loading = true;

        try {
            if ($this->currentStep < $this->totalSteps) {
                // Validate current step before proceeding
                if ($this->validateCurrentStep()) {
                    $this->currentStep++;

                    // Perform step-specific actions
                    $this->handleStepTransition();
                    $this->is_loading = false;
                } else {
                    $this->is_loading = false;
                }
            } else {
                $this->is_loading = false;
            }

        } catch (ValidationException $e) {
            $this->is_loading = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading = false;
            Log::error('Checkout step navigation error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'step' => $this->currentStep,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'An error occurred. Please try again.', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
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
                return $this->validatePaymentInfo();

            default:
                return true;
        }
    }

    private function validatePaymentInfo()
    {
        try {
            $rules = [
                'payment_method' => 'required|in:crypto,stripe,wallet,mir',
                'agree_to_terms' => 'accepted',
            ];

            // Add crypto currency validation if crypto is selected
            if ($this->payment_method === 'crypto') {
                $rules['crypto_currency'] = 'required|string|in:btc,eth,xmr,ltc,doge,ada,dot,sol';
            }

            // Add wallet balance validation if wallet is selected
            if ($this->payment_method === 'wallet') {
                $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
                $walletBalance = $customer->wallet ? $customer->wallet->balance : 0;
                if ($walletBalance < ($this->order_summary['total'] ?? 0)) {
                    throw ValidationException::withMessages([
                        'payment_method' => ['Insufficient wallet balance. Please top up your wallet or choose a different payment method.']
                    ]);
                }
            }

            $this->validate($rules);
            return true;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateBillingInfo()
    {
        try {
            $this->validate([
                'first_name' => 'required|string|max:255|min:2',
                'last_name' => 'required|string|max:255|min:2',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'country' => 'required|string|max:255',
            ]);
            return true;
        } catch (ValidationException $e) {
            throw $e;
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
        $this->is_applying_coupon = true;

        try {
            // Rate limiting
            $key = 'coupon_apply.' . Auth::guard('customer')->id();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                throw ValidationException::withMessages([
                    'coupon_code' => ["Too many coupon attempts. Please wait 10 minutes before trying again."],
                ]);
            }

            if (empty($this->coupon_code)) {
                $this->alert('error', 'Please enter a coupon code');
                $this->is_applying_coupon = false;
                return;
            }

            // Validate coupon
            $discount = $this->validateCoupon($this->coupon_code);

            if ($discount > 0) {
                $this->discount_amount = $discount;
                $this->applied_coupon = $this->coupon_code;
                $this->calculateOrderSummary();

                // Clear rate limit on success
                RateLimiter::clear($key);

                $this->alert('success', "Coupon applied! You saved $" . number_format($discount, 2), [
                    'position' => 'top-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
            } else {
                RateLimiter::hit($key, 600); // 10 minutes = 600 seconds
                
                $this->alert('error', 'Invalid or expired coupon code', [
                    'position' => 'top-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
            }

            $this->is_applying_coupon = false;

        } catch (ValidationException $e) {
            $this->is_applying_coupon = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_applying_coupon = false;
            Log::error('Coupon application error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'coupon_code' => $this->coupon_code,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to apply coupon. Please try again.', [
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

    public function processOrder()
    {
        if ($this->is_processing) {
            return;
        }

        $this->is_processing = true;

        try {
            // Validate terms agreement before processing
            if (!$this->agree_to_terms) {
                throw new \Exception('You must agree to the terms and conditions to proceed.');
            }

            // Rate limiting for order processing (more lenient)
            $key = 'order_process.' . Auth::guard('customer')->id();
            if (RateLimiter::tooManyAttempts($key, 3)) { // 3 attempts per 30 minutes
                throw new \Exception("Too many order attempts. Please wait 30 minutes before trying again.");
            }

            // Submit form data to CheckoutController for payment processing
            $this->submitToCheckoutController();

            // Only hit rate limiter after successful submission attempt
            RateLimiter::hit($key, 1800); // 30 minutes = 1800 seconds

        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();

            Log::error('Order processing failed', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'cart_items' => $this->cart_items,
                'payment_method' => $this->payment_method,
                'ip' => request()->ip()
            ]);

            $this->alert('error', 'Order processing failed: ' . $e->getMessage(), [
                'position' => 'top-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        } finally {
            $this->is_processing = false;
        }
    }

    /**
     * Submit checkout data to CheckoutController
     */
    private function submitToCheckoutController()
    {
        // Transform cart items to match CheckoutController expectations
        $transformedCartItems = [];
        foreach ($this->cart_items as $item) {
            $transformedCartItems[] = [
                'server_plan_id' => $item['server_plan_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'], // Map 'price' to 'unit_price'
                'total_price' => $item['total_amount'], // Map 'total_amount' to 'total_price'
            ];
        }

        // Prepare form data for submission
        $formData = [
            // Billing information
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            
            // Payment information
            'payment_method' => $this->payment_method,
            'crypto_currency' => $this->crypto_currency,
            'save_payment_method' => $this->save_payment_method,
            'agree_to_terms' => $this->agree_to_terms,
            'subscribe_newsletter' => $this->subscribe_newsletter,
            
            // Order information
            'coupon_code' => $this->applied_coupon,
            'discount_amount' => $this->discount_amount,
            
            // Cart items (transformed)
            'cart_items' => $transformedCartItems,
            'order_summary' => $this->order_summary,
        ];

        // Use HTTP client to submit to PaymentController API
        $response = \Http::withToken(Auth::user()->api_token ?? session('api_token'))
            ->post(url('/api/payment/create-payment'), $formData);

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['redirect_url'])) {
                // External payment gateway - redirect
                return redirect()->away($responseData['redirect_url']);
            } elseif (isset($responseData['success']) && $responseData['success']) {
                // Internal payment (wallet) - show success
                $this->handlePaymentSuccess($responseData);
            } else {
                throw new \Exception($responseData['error'] ?? 'Payment processing failed');
            }
        } else {
            $errorData = $response->json();
            throw new \Exception($errorData['error'] ?? 'Failed to process checkout');
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess($responseData)
    {
        // Clear cart
        CartManagement::clearCartItems();

        $this->currentStep = 4; // Confirmation step
        $this->order_created = $responseData['order'] ?? null;

        $this->alert('success', 'Order completed successfully!', [
            'position' => 'center',
            'timer' => false,
            'toast' => false,
        ]);

        // Security logging
        Log::info('Order completed successfully via Livewire', [
            'order_id' => $responseData['order']['id'] ?? null,
            'customer_id' => Auth::guard('customer')->id(),
            'payment_method' => $this->payment_method,
            'ip' => request()->ip(),
        ]);
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
    $this->error_message = is_array($error) ? ($error['error'] ?? 'Payment failed. Please try again.') : (string)$error;
    $this->alert('error', $this->error_message);
    }

    public function goToOrders()
    {
        return redirect()->route('my.orders');
    }

    public function continueShopping()
    {
        return redirect()->route('servers.index');
    }

    public function render()
    {
        return view('livewire.checkout-page', [
            'stepProgress' => $this->stepProgress,
            'orderSummary' => $this->order_summary,
        ]);
    }
}
