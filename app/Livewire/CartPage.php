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
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Collection;

#[Title('Shopping Cart - 1000 PROXIES')]
class CartPage extends Component
{
    use LivewireAlert;

    public $order_items = [];
    public $grand_amount = 0;
    public $shipping_amount = 0;
    public $tax_amount = 0;
    public $discount_amount = 0;
    public $coupon_code = '';
    public $applied_coupon = null;
    public $show_coupon_form = false;

    // Cart persistence
    public $save_for_later = [];
    public $recently_viewed = [];

    protected $listeners = [
        'cartUpdated' => 'refreshCart',
        'couponApplied' => 'applyCoupon'
    ];

    public function mount()
    {
        $this->refreshCart();
        $this->loadRecentlyViewed();
    }

    #[Computed]
    public function cartSummary()
    {
        $subtotal = $this->grand_amount;
        $tax = $this->calculateTax($subtotal);
        $shipping = $this->calculateShipping();
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

    #[Computed]
    public function recommendedPlans()
    {
        // Get plans based on cart items categories
        $categoryIds = collect($this->order_items)->pluck('server_plan.server_category_id')->unique();

        return ServerPlan::whereIn('server_category_id', $categoryIds)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->whereNotIn('id', collect($this->order_items)->pluck('server_plan_id'))
            ->with(['brand', 'category'])
            ->limit(3)
            ->get();
    }

    public function refreshCart()
    {
        $this->order_items = CartManagement::getCartItemsFromCookie();
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
    }

    private function recalculateAmounts()
    {
        $this->tax_amount = $this->calculateTax($this->grand_amount);
        $this->shipping_amount = $this->calculateShipping();
    }

    private function calculateTax($amount)
    {
        // Calculate tax based on location or default rate
        $taxRate = 0.08; // 8% default tax rate
        return round($amount * $taxRate, 2);
    }

    private function calculateShipping()
    {
        // Free shipping for orders over $50
        if ($this->grand_amount >= 50) {
            return 0;
        }
        return 5.99; // Standard shipping
    }

    public function removeItem($server_plan_id)
    {
        $this->order_items = CartManagement::removeCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();

        $this->dispatch('update-cart-count', total_count: count($this->order_items))->to(Navbar::class);
        $this->dispatch('cartUpdated');

        $this->alert('success', 'Item removed from cart!', [
            'position' => 'bottom-end',
            'timer' => 2000,
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    public function increaseQty($server_plan_id)
    {
        $this->order_items = CartManagement::incrementQuantityToCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
        $this->dispatch('cartUpdated');
    }

    public function decreaseQty($server_plan_id)
    {
        $this->order_items = CartManagement::decrementQuantityToCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
        $this->dispatch('cartUpdated');
    }

    public function updateQuantity($server_plan_id, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeItem($server_plan_id);
            return;
        }

        $this->order_items = CartManagement::updateItemQuantity($server_plan_id, $quantity);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->recalculateAmounts();
        $this->dispatch('cartUpdated');
    }

    public function saveForLater($server_plan_id)
    {
        // Move item from cart to saved items
        $item = collect($this->order_items)->where('server_plan_id', $server_plan_id)->first();

        if ($item) {
            $this->save_for_later[] = $item;
            $this->removeItem($server_plan_id);

            // Persist to session or cookie
            session()->put('save_for_later', $this->save_for_later);

            $this->alert('info', 'Item saved for later!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }

    public function moveToCart($index)
    {
        if (isset($this->save_for_later[$index])) {
            $item = $this->save_for_later[$index];

            // Add back to cart
            CartManagement::addItemToCartWithQty($item['server_plan_id'], $item['quantity']);

            // Remove from saved items
            unset($this->save_for_later[$index]);
            $this->save_for_later = array_values($this->save_for_later);
            session()->put('save_for_later', $this->save_for_later);

            $this->refreshCart();

            $this->alert('success', 'Item moved to cart!', [
                'position' => 'bottom-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }

    public function toggleCouponForm()
    {
        $this->show_coupon_form = !$this->show_coupon_form;
    }

    public function applyCouponCode()
    {
        if (empty($this->coupon_code)) {
            $this->alert('error', 'Please enter a coupon code');
            return;
        }

        // Validate coupon code (this would integrate with a coupons system)
        $discount = $this->validateAndCalculateDiscount($this->coupon_code);

        if ($discount > 0) {
            $this->discount_amount = $discount;
            $this->applied_coupon = $this->coupon_code;
            $this->show_coupon_form = false;

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

    private function validateAndCalculateDiscount($code)
    {
        // Mock coupon validation - replace with real coupon system
        $coupons = [
            'SAVE10' => 0.10, // 10% discount
            'WELCOME' => 5.00, // $5 off
            'FIRST20' => 0.20, // 20% discount
        ];

        if (isset($coupons[$code])) {
            $discountRate = $coupons[$code];

            if ($discountRate < 1) {
                // Percentage discount
                return round($this->grand_amount * $discountRate, 2);
            } else {
                // Fixed amount discount
                return min($discountRate, $this->grand_amount);
            }
        }

        return 0;
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
        CartManagement::clearCartItems();
        $this->order_items = [];
        $this->grand_amount = 0;
        $this->recalculateAmounts();

        $this->dispatch('update-cart-count', total_count: 0)->to(Navbar::class);
        $this->dispatch('cartUpdated');

        $this->alert('info', 'Cart cleared!', [
            'position' => 'bottom-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function addRecommendedToCart($planId)
    {
        $total_count = CartManagement::addItemToCart($planId);
        $this->refreshCart();

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Recommended item added to cart!', [
            'position' => 'bottom-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    private function loadRecentlyViewed()
    {
        $this->recently_viewed = session()->get('recently_viewed', []);
    }

    #[On('cartUpdated')]
    public function handleCartUpdate()
    {
        $this->refreshCart();
    }

    public function render()
    {
        return view('livewire.cart-page', [
            'cartSummary' => $this->cartSummary,
            'recommendedPlans' => $this->recommendedPlans,
            'saveForLater' => session()->get('save_for_later', []),
        ]);
    }
}
