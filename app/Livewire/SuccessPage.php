<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Mail\OrderPlaced;
use App\Services\EnhancedMailService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Jobs\ProcessXuiOrder;

#[Title('Order Success - 1000 PROXIES')]
class SuccessPage extends Component
{
    use LivewireAlert;

    #[Url]
    public $session_id;

    #[Url]
    public $payment_id;

    #[Url]
    public $order_id;

    public $order = null;
    public $orderItems = [];
    public $paymentDetails = [];
    public $showOrderDetails = false;
    public $showInvoiceModal = false;
    public $showSupportModal = false;

    // Upsell and cross-sell
    public $relatedProducts = [];
    public $recommendedProducts = [];
    public $showProductRecommendations = true;

    // User feedback
    public $orderRating = 0;
    public $orderFeedback = '';
    public $showFeedbackForm = false;

    // Follow-up actions
    public $subscribeToNewsletter = false;
    public $joinLoyaltyProgram = false;
    public $enableNotifications = true;

    public function mount()
    {
        // Get the latest order or specific order by ID
        if ($this->order_id) {
            $this->order = Order::with(['invoice', 'paymentMethod', 'customer', 'orderItems.serverPlan'])
                               ->where('id', $this->order_id)
                               ->where('customer_id', Auth::guard('customer')->id())
                               ->first();
        } else {
            $this->order = Order::with(['invoice', 'paymentMethod', 'customer', 'orderItems.serverPlan'])
                               ->where('customer_id', Auth::guard('customer')->id())
                               ->latest()
                               ->first();
        }

        if (!$this->order) {
            return redirect()->route('my.orders');
        }

        $this->orderItems = $this->order->orderItems;

        // Process payment verification and order completion
        $this->processPaymentVerification();

        // Load related products for upselling
        $this->loadRelatedProducts();

        // Clear cart after successful order
        session()->forget('cart');
    }

    private function processPaymentVerification()
    {
        if (!$this->order->paymentMethod) {
            return redirect()->route('cancel');
        }

        $slug = $this->order->paymentMethod->slug;

        switch ($slug) {
            case 'stripe':
                $this->processStripePayment();
                break;
            case 'nowpayments':
                $this->processNowPayment();
                break;
            case 'paypal':
                $this->processPayPalPayment();
                break;
            case 'wallet':
                $this->processWalletPayment();
                break;
            case 'bank_transfer':
                $this->processBankTransfer();
                break;
            default:
                $this->processGenericPayment();
                break;
        }

        // Process XUI Order if payment is successful
        if ($this->order->payment_status === 'paid' && !$this->order->is_completed) {
            $this->finalizeOrder();
        }
    }

    private function processStripePayment()
    {
        if (!$this->session_id) {
            return;
        }

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session_info = Session::retrieve($this->session_id);

            $this->paymentDetails = [
                'payment_method' => 'Stripe',
                'payment_intent_id' => $session_info->payment_intent ?? null,
                'session_id' => $this->session_id,
                'amount_paid' => $session_info->amount_total / 100,
                'currency' => strtoupper($session_info->currency),
                'payment_status' => $session_info->payment_status,
            ];

            if ($session_info->payment_status === 'paid') {
                $this->order->update([
                    'payment_status' => 'paid',
                    'stripe_session_id' => $this->session_id,
                    'paid_at' => now(),
                ]);
            } else {
                $this->order->update(['payment_status' => 'failed']);
                return redirect()->route('cancel');
            }
        } catch (\Exception $e) {
            $this->order->update(['payment_status' => 'failed']);
            return redirect()->route('cancel');
        }
    }

    private function processNowPayment()
    {
        try {
            $response = Http::get(route('payment.status', ['orderId' => $this->order->id]));

            if ($response->successful()) {
                $paymentData = $response->json();
                $payment_status = $paymentData['payment_status'] ?? 'pending';

                $this->paymentDetails = [
                    'payment_method' => 'NowPayments (Crypto)',
                    'payment_id' => $this->payment_id,
                    'payment_status' => $payment_status,
                    'crypto_amount' => $paymentData['crypto_amount'] ?? null,
                    'crypto_currency' => $paymentData['crypto_currency'] ?? null,
                ];

                if ($payment_status === 'finished') {
                    $this->order->update([
                        'payment_status' => 'paid',
                        'crypto_payment_id' => $this->payment_id,
                        'paid_at' => now(),
                    ]);
                } else {
                    $this->order->update(['payment_status' => 'pending']);
                    return redirect()->route('my.orders');
                }
            } else {
                $this->order->update(['payment_status' => 'failed']);
                return redirect()->route('cancel');
            }
        } catch (\Exception $e) {
            $this->order->update(['payment_status' => 'failed']);
            return redirect()->route('cancel');
        }
    }

    private function processPayPalPayment()
    {
        // PayPal payment processing logic
        $this->paymentDetails = [
            'payment_method' => 'PayPal',
            'payment_id' => $this->payment_id,
        ];

        if ($this->order->payment_status !== 'paid') {
            $this->order->update([
                'payment_status' => 'paid',
                'paypal_payment_id' => $this->payment_id,
                'paid_at' => now(),
            ]);
        }
    }

    private function processWalletPayment()
    {
        $this->paymentDetails = [
            'payment_method' => 'Wallet Balance',
            'amount_paid' => $this->order->grand_total,
        ];

        if ($this->order->payment_status !== 'paid') {
            $this->order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        }
    }

    private function processBankTransfer()
    {
        $this->paymentDetails = [
            'payment_method' => 'Bank Transfer',
            'status' => 'Verification Pending',
        ];

        $this->order->update(['payment_status' => 'pending']);
    }

    private function processGenericPayment()
    {
        $this->paymentDetails = [
            'payment_method' => $this->order->paymentMethod->name ?? 'Unknown',
        ];
    }

    private function finalizeOrder()
    {
        try {
            // Process XUI Order
            ProcessXuiOrder::dispatch($this->order);

            // Update order status
            $this->order->update([
                'status' => 'processing',
                'is_completed' => true,
                'processed_at' => now(),
            ]);

            // Send confirmation email
            $this->sendOrderConfirmationEmail();

            // Award loyalty points
            $this->awardLoyaltyPoints();

            // Track conversion for analytics
            $this->trackConversion();

        } catch (\Exception $e) {
            Log::error('Order finalization failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendOrderConfirmationEmail()
    {
        try {
            // Use the enhanced mail service for better error handling and logging
            $mailService = app(EnhancedMailService::class);
            $success = $mailService->sendOrderPlacedEmail($this->order);

            if ($success) {
                Log::info('Order confirmation email sent successfully via EnhancedMailService', [
                    'order_id' => $this->order->id,
                    'email' => $this->order->user->email
                ]);
            } else {
                Log::warning('Order confirmation email failed via EnhancedMailService', [
                    'order_id' => $this->order->id,
                    'email' => $this->order->user->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Order confirmation email failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function awardLoyaltyPoints()
    {
        $pointsEarned = floor($this->order->grand_total); // 1 point per dollar

        if ($pointsEarned > 0) {
            $this->order->customer->increment('loyalty_points', $pointsEarned);
        }
    }

    private function trackConversion()
    {
        // Analytics tracking for successful conversion
    }

    private function loadRelatedProducts()
    {
        // Get related products based on purchased items
        $categoryIds = collect($this->orderItems)->pluck('serverPlan.category_id')->unique()->filter();

        $this->relatedProducts = \App\Models\ServerPlan::whereIn('category_id', $categoryIds->toArray())
                                                      ->where('is_active', true)
                                                      ->whereNotIn('id', collect($this->orderItems)->pluck('server_plan_id')->toArray())
                                                      ->inRandomOrder()
                                                      ->limit(4)
                                                      ->get();

        // Get recommended products based on user behavior
        $this->recommendedProducts = \App\Models\ServerPlan::where('is_featured', true)
                                                          ->where('is_active', true)
                                                          ->whereNotIn('id', collect($this->orderItems)->pluck('server_plan_id')->toArray())
                                                          ->inRandomOrder()
                                                          ->limit(4)
                                                          ->get();
    }

    // Order actions
    public function downloadInvoice()
    {
        if (!$this->order->invoice) {
            $this->alert('error', 'Invoice not available yet.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        // Generate and download invoice
        $this->alert('info', 'Invoice download started...', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function reorderItems()
    {
        foreach ($this->orderItems as $item) {
            if ($item->serverPlan && $item->serverPlan->is_active) {
                // Add to cart using session
                $cart = session()->get('cart', []);
                $cart[$item->server_plan_id] = [
                    'quantity' => $item->quantity,
                    'server_plan' => $item->serverPlan,
                ];
                session()->put('cart', $cart);
            }
        }

        $this->alert('success', 'Items added to cart!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        return redirect()->route('cart');
    }

    public function addToCart($productId, $quantity = 1)
    {
        // Add to cart using session
        $cart = session()->get('cart', []);
        $serverPlan = \App\Models\ServerPlan::find($productId);

        if ($serverPlan) {
            $cart[$productId] = [
                'quantity' => $quantity,
                'server_plan' => $serverPlan,
            ];
            session()->put('cart', $cart);
        }

        $this->alert('success', 'Product added to cart!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Feedback and follow-up
    public function submitFeedback()
    {
        $this->validate([
            'orderRating' => 'required|integer|min:1|max:5',
            'orderFeedback' => 'nullable|string|max:500',
        ]);

        // Save feedback
        $this->order->update([
            'customer_rating' => $this->orderRating,
            'customer_feedback' => $this->orderFeedback,
        ]);

        $this->showFeedbackForm = false;

        $this->alert('success', 'Thank you for your feedback!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function subscribeNewsletter()
    {
        // Newsletter subscription logic would go here
        $this->alert('success', 'Successfully subscribed to newsletter!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function joinLoyalty()
    {
        // Loyalty program enrollment logic would go here
        $this->alert('success', 'Welcome to our loyalty program!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Order tracking and support
    public function trackOrder()
    {
        return redirect()->route('my-order-detail', ['order' => $this->order->id]);
    }

    public function contactSupport()
    {
        $this->showSupportModal = true;
    }

    public function getOrderEstimatedDelivery()
    {
        // Calculate estimated delivery based on order items
        return now()->addHours(1)->format('M d, Y H:i A');
    }

    public function getOrderProgress()
    {
        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
        $currentIndex = array_search($this->order->status, $statuses);

        return [
            'current_step' => $currentIndex + 1,
            'total_steps' => count($statuses),
            'percentage' => (($currentIndex + 1) / count($statuses)) * 100,
        ];
    }

    public function render()
    {
        if (!$this->order) {
            return redirect()->route('my.orders');
        }

        $orderProgress = $this->getOrderProgress();
        $estimatedDelivery = $this->getOrderEstimatedDelivery();

        return view('livewire.success-page', [
            'order' => $this->order,
            'orderItems' => $this->orderItems,
            'paymentDetails' => $this->paymentDetails,
            'relatedProducts' => $this->relatedProducts,
            'recommendedProducts' => $this->recommendedProducts,
            'orderProgress' => $orderProgress,
            'estimatedDelivery' => $estimatedDelivery,
        ]);
    }
}
