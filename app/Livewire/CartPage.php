<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use Livewire\WithPagination;
use App\Models\ServerInbound;
use App\Models\ServerCategory;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Livewire\Traits\LivewireAlertV4;
use Illuminate\Support\Collection;

#[Title('Shopping Cart - 1000 PROXIES')]
class CartPage extends Component
{
    use LivewireAlertV4;

    /** @var array<int, mixed> */
    public array $order_items = [];
    public float $grand_amount = 0;
    public float $shipping_amount = 0;
    public float $tax_amount = 0;
    public float $discount_amount = 0;
    public string $coupon_code = '';
    public ?string $applied_coupon = null;
    // Basic location context for tax/shipping rules (mirrors CheckoutPage behavior)
    public string $country = 'US';
    
    // Loading states
    public bool $is_loading = false;
    public bool $is_applying_coupon = false;
    public bool $is_updating_quantity = false;
    
    public bool $show_coupon_form = false;

    // Cart persistence
    /** @var array<int, mixed> */
    public array $save_for_later = [];
    /** @var array<int, mixed> */
    public array $recently_viewed = [];

    protected $listeners = [
        'cartUpdated' => 'refreshCart',
    ];

    protected function rules()
    {
        return [
            'coupon_code' => 'nullable|string|max:50',
        ];
    }
    /**
     * Initialize the cart page by loading cart items, recently viewed products, and saved-for-later items from session.
     */
    public function mount()
    {
        $this->refreshCart();
        $this->loadRecentlyViewed();
        $this->loadSaveForLater();
    }

    private function loadSaveForLater()
    {
        $this->save_for_later = session()->get('save_for_later', []);
    }

    #[Computed]
    public function cartSummary()
    {
        $subtotal = $this->grand_amount;
        $tax = $this->tax_amount;
        $shipping = $this->shipping_amount;
        $discount = $this->discount_amount;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $subtotal + $tax + $shipping - $discount,
            'items_count' => count($this->order_items),
        ];
    }

    private ?Collection $cachedRecommendedPlans = null;

    #[Computed]
    public function recommendedPlans()
    {
        if ($this->cachedRecommendedPlans !== null) {
            return $this->cachedRecommendedPlans;
        }

        // Get plans based on cart items categories, with a safe fallback when none match
        $categoryIds = collect($this->order_items)->pluck('server_plan.server_category_id')->filter()->unique();
        $inCart = collect($this->order_items)->pluck('server_plan_id')->filter();

        $query = ServerPlan::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->whereNotIn('id', $inCart)
            ->with(['brand', 'category']);

        if ($categoryIds->isNotEmpty()) {
            $query->whereIn('server_category_id', $categoryIds);
        }

        $plans = $query->limit(3)->get();

        // Fallback: return featured active plans (not in cart) when category-based query yields none
        if ($plans->isEmpty()) {
            $plans = ServerPlan::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->whereNotIn('id', $inCart)
                ->with(['brand', 'category'])
                ->limit(3)
                ->get();
        }

        return $this->cachedRecommendedPlans = $plans;
    }

    public function refreshCart()
    {
        $this->order_items = CartManagement::getCartItemsFromCookie();

        // Test-mode fallback: when cookies aren't persisted in Livewire tests, seed a default item
    // Do not auto-seed items in tests; honor actual cookie/session state

        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
        $this->cachedRecommendedPlans = null; // Invalidate cache when cart changes
    }

    private function recalculateAmounts()
    {
        $this->tax_amount = $this->calculateTax($this->grand_amount);
        $this->shipping_amount = $this->calculateShipping();
    }

    private function calculateTax($amount)
    {
    // Digital goods: tax disabled sitewide (align with CheckoutPage)
    return 0.0;
    }

    // Recalculate when country changes (used by tests asserting variable tax)
    public function updatedCountry(): void
    {
        $this->recalculateAmounts();
    }

    private function calculateShipping()
    {
    // Digital goods: no physical shipping cost (align with CheckoutPage)
    return 0.0;
    }

    public function removeItem($server_plan_id)
    {
        try {
            $this->is_loading = true;

            $this->order_items = CartManagement::removeCartItem($server_plan_id);
            $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
            $this->recalculateAmounts();

            $this->dispatch('update-cart-count', total_count: count($this->order_items))->to(\App\Livewire\Partials\Navbar::class);
            $this->dispatch('cartUpdated');

            $this->is_loading = false;

            $this->alert('success', 'Item removed from cart!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
                'timerProgressBar' => true,
            ]);

        } catch (\Exception $e) {
            $this->is_loading = false;
            Log::error('Cart item removal error', [
                'error' => $e->getMessage(),
                'server_plan_id' => $server_plan_id,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to remove item. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function increaseQty($server_plan_id)
    {
        $this->order_items = CartManagement::incrementQuantityToCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
    $this->dispatch('update-cart-count', total_count: count($this->order_items))->to(\App\Livewire\Partials\Navbar::class);
    $this->dispatch('cartUpdated');
    }

    public function decreaseQty($server_plan_id)
    {
        $this->order_items = CartManagement::decrementQuantityToCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
    $this->dispatch('update-cart-count', total_count: count($this->order_items))->to(\App\Livewire\Partials\Navbar::class);
    $this->dispatch('cartUpdated');
    }

    public function updateQuantity($server_plan_id, $quantity)
    {
        try {
            $this->is_updating_quantity = true;

            // Rate limiting for quantity updates
            $key = 'cart_update.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many cart updates. Please try again in {$seconds} seconds.");
            }

            RateLimiter::hit($key, 60); // 1-minute window

            if ($quantity <= 0) {
                $this->removeItem($server_plan_id);
                $this->is_updating_quantity = false;
                return;
            }

            $this->order_items = CartManagement::updateItemQuantity($server_plan_id, $quantity);
            $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
            $this->recalculateAmounts();
            $this->dispatch('cartUpdated');

            $this->is_updating_quantity = false;

        } catch (\Exception $e) {
            $this->is_updating_quantity = false;
            Log::error('Cart quantity update error', [
                'error' => $e->getMessage(),
                'server_plan_id' => $server_plan_id,
                'quantity' => $quantity,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to update quantity. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }
    public function saveForLater($server_plan_id)
    {
        // Move item from cart to saved items
        $item = collect($this->order_items)->where('server_plan_id', $server_plan_id)->first();

        if ($item) {
            $saveForLater = session()->get('save_for_later', []);
            $saveForLater[] = $item;
            session()->put('save_for_later', $saveForLater);
            $this->save_for_later = $saveForLater;

            $this->removeItem($server_plan_id);

            $this->alert('info', 'Item saved for later!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }
    /**
     * Move item from saved for later back to cart
     */
    public function moveToCart($index)
    {
        $saveForLater = session()->get('save_for_later', []);
        if (isset($saveForLater[$index])) {
            $item = $saveForLater[$index];

            // Add back to cart
            $total_count = CartManagement::addItemToCartWithQty($item['server_plan_id'], $item['quantity']);
            $this->dispatch('update-cart-count', total_count: $total_count)->to(\App\Livewire\Partials\Navbar::class);

            // Remove from saved items
            unset($saveForLater[$index]);
            $saveForLater = array_values($saveForLater);
            session()->put('save_for_later', $saveForLater);
            $this->save_for_later = $saveForLater;

            $this->refreshCart();
            $this->dispatch('cartUpdated');

            $this->alert('success', 'Item moved to cart!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }

    /**
     * Toggle visibility of the coupon code form
     */

    public function toggleCouponForm()
    {
        $this->show_coupon_form = !$this->show_coupon_form;
    }

    public function applyCouponCode()
    {
        $this->is_applying_coupon = true;

        try {
            // Ensure latest cart totals are in memory before applying a coupon
            $this->refreshCart();
            // Rate limiting for coupon applications
            $key = 'coupon_apply.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'coupon_code' => ["Too many coupon attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            if (empty($this->coupon_code)) {
                $this->alert('error', 'Please enter a coupon code');
                $this->is_applying_coupon = false;
                return;
            }

            // Validate coupon code
            $discount = $this->validateAndCalculateDiscount($this->coupon_code);

            if ($discount > 0) {
                $this->discount_amount = $discount;
                $this->applied_coupon = $this->coupon_code;
                $this->show_coupon_form = false;

                // Clear rate limit on success
                RateLimiter::clear($key);

                $this->alert('success', "Coupon applied! You saved $" . number_format($discount, 2), [
                    'position' => 'top-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
            } else {
                RateLimiter::hit($key, 300); // 5-minute window
                
                $this->alert('error', 'Invalid coupon code');
            }

            $this->is_applying_coupon = false;

        } catch (ValidationException $e) {
            $this->is_applying_coupon = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_applying_coupon = false;
            Log::error('Coupon application error', [
                'error' => $e->getMessage(),
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

    private function validateAndCalculateDiscount($code)
    {
        // Use DB-backed coupons
        $coupon = \App\Models\Coupon::where('code', $code)->where('is_active', true)->first();
        if (!$coupon) return 0;

        $customerId = \Illuminate\Support\Facades\Auth::guard('customer')->id();
        if ($coupon->single_use_per_customer && $customerId) {
            $used = \App\Models\CouponUsage::where('coupon_id', $coupon->id)->where('customer_id', $customerId)->exists();
            if ($used) return 0;
        }

        $subtotal = \App\Helpers\CartManagement::calculateGrandTotal($this->order_items);
        if ($coupon->type === 'percent') {
            return round($subtotal * ($coupon->value / 100), 2);
        }
        return min((float)$coupon->value, $subtotal);
    }

    public function removeCoupon()
    {
        $this->discount_amount = 0;
        $this->applied_coupon = null;
        $this->coupon_code = '';

        $this->alert('info', 'Coupon removed', [
            'position' => 'bottom-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function clearCart()
    {
        try {
            $this->is_loading = true;

            CartManagement::clearCartItems();
            $this->order_items = [];
            $this->grand_amount = 0;
            $this->recalculateAmounts();

            $this->dispatch('update-cart-count', total_count: 0)->to(\App\Livewire\Partials\Navbar::class);
            $this->dispatch('cartUpdated');

            $this->is_loading = false;

            $this->alert('info', 'Cart cleared!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
            ]);

        } catch (\Exception $e) {
            $this->is_loading = false;
            Log::error('Cart clear error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to clear cart. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function addRecommendedToCart($planId)
    {
        $total_count = CartManagement::addItemToCart($planId);
        $this->refreshCart();

        $this->dispatch('update-cart-count', total_count: $total_count)->to(\App\Livewire\Partials\Navbar::class);

            // Ensure immediate client-side update
            $this->dispatch('cartUpdated');
            $this->dispatch('cart-count-updated', ['count' => $total_count]);

        $this->alert('success', 'Recommended item added to cart!', [
            'position' => 'bottom-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    /**
     * Loads the recently viewed items from the session into the component property.
     */
    private function loadRecentlyViewed()
    {
        $this->recently_viewed = session()->get('recently_viewed', []);
    }
    /**
     * Passes the following data to the view:
     * - cartSummary: array containing subtotal, tax, shipping, discount, total, and items_count
     * - recommendedPlans: collection of recommended server plans
     * - saveForLater: array of items saved for later
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Always load latest save_for_later from session before rendering
        $this->loadSaveForLater();

        return view('livewire.cart-page', [
            'cartSummary' => $this->cartSummary,
            'recommendedPlans' => $this->recommendedPlans,
            'saveForLater' => $this->save_for_later,
        ]);
    }
}