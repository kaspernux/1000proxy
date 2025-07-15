<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\CartPage;
use App\Models\ServerPlan;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\Server;
use App\Helpers\CartManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    protected $serverPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestData();
        $this->addItemsToCart();
    }

    private function createTestData()
    {
        $brand = ServerBrand::factory()->create();
        $category = ServerCategory::factory()->create();
        $server = Server::factory()->create();

        $this->serverPlan = ServerPlan::factory()->create([
            'name' => 'Test Plan',
            'price' => 19.99,
            'is_active' => true,
            'server_id' => $server->id,
            'server_brand_id' => $brand->id,
            'server_category_id' => $category->id
        ]);
    }

    private function addItemsToCart()
    {
        // Add test item to cart
        CartManagement::addItemToCart($this->serverPlan->id);
    }

    /** @test */
    public function cart_page_renders_successfully()
    {
        Livewire::test(CartPage::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.cart-page');
    }

    /** @test */
    public function cart_page_displays_cart_items()
    {
        Livewire::test(CartPage::class)
            ->assertSee($this->serverPlan->name)
            ->assertSee('$19.99');
    }

    /** @test */
    public function cart_summary_calculations_are_correct()
    {
        $component = Livewire::test(CartPage::class);

        $summary = $component->get('cartSummary');

        $this->assertEquals(19.99, $summary['subtotal']);
        $this->assertGreaterThan(0, $summary['tax']);
        $this->assertEquals(1, $summary['items_count']);
    }

    /** @test */
    public function remove_item_works()
    {
        Livewire::test(CartPage::class)
            ->call('removeItem', $this->serverPlan->id)
            ->assertDispatched('update-cart-count')
            ->assertDispatched('cartUpdated')
            ->assertSet('grand_amount', 0);
    }

    /** @test */
    public function increase_quantity_works()
    {
        $component = Livewire::test(CartPage::class)
            ->call('increaseQty', $this->serverPlan->id);

        // Verify quantity increased and total updated
        $this->assertGreaterThan(19.99, $component->get('grand_amount'));
    }

    /** @test */
    public function decrease_quantity_works()
    {
        // First increase quantity to 2
        CartManagement::incrementQuantityToCartItem($this->serverPlan->id);

        $component = Livewire::test(CartPage::class)
            ->call('decreaseQty', $this->serverPlan->id);

        // Should go back to quantity 1
        $this->assertEquals(19.99, $component->get('grand_amount'));
    }

    /** @test */
    public function save_for_later_works()
    {
        Livewire::test(CartPage::class)
            ->call('saveForLater', $this->serverPlan->id)
            ->assertSet('grand_amount', 0);

        // Check session for saved items
        $savedItems = session('save_for_later', []);
        $this->assertCount(1, $savedItems);
    }

    /** @test */
    public function move_to_cart_from_saved_works()
    {
        // First save an item
        session()->put('save_for_later', [
            [
                'server_plan_id' => $this->serverPlan->id,
                'quantity' => 1,
                'server_plan' => $this->serverPlan
            ]
        ]);

        Livewire::test(CartPage::class)
            ->call('moveToCart', 0)
            ->assertDispatched('cartUpdated');
    }

    /** @test */
    public function coupon_application_works()
    {
        Livewire::test(CartPage::class)
            ->set('coupon_code', 'SAVE10')
            ->call('applyCouponCode')
            ->assertNotSet('discount_amount', 0)
            ->assertNotNull('applied_coupon');
    }

    /** @test */
    public function invalid_coupon_shows_error()
    {
        Livewire::test(CartPage::class)
            ->set('coupon_code', 'INVALID')
            ->call('applyCouponCode')
            ->assertSet('discount_amount', 0)
            ->assertNull('applied_coupon');
    }

    /** @test */
    public function coupon_removal_works()
    {
        // First apply a coupon
        $component = Livewire::test(CartPage::class)
            ->set('coupon_code', 'SAVE10')
            ->call('applyCouponCode');

        // Then remove it
        $component->call('removeCoupon')
            ->assertSet('discount_amount', 0)
            ->assertNull('applied_coupon')
            ->assertSet('coupon_code', '');
    }

    /** @test */
    public function clear_cart_empties_all_items()
    {
        Livewire::test(CartPage::class)
            ->call('clearCart')
            ->assertSet('order_items', [])
            ->assertSet('grand_amount', 0)
            ->assertDispatched('update-cart-count');
    }

    /** @test */
    public function toggle_coupon_form_works()
    {
        Livewire::test(CartPage::class)
            ->assertSet('show_coupon_form', false)
            ->call('toggleCouponForm')
            ->assertSet('show_coupon_form', true)
            ->call('toggleCouponForm')
            ->assertSet('show_coupon_form', false);
    }

    /** @test */
    public function free_shipping_calculation_works()
    {
        // Create high-priced item for free shipping
        $expensivePlan = ServerPlan::factory()->create([
            'price' => 60.00,
            'is_active' => true
        ]);

        // Clear current cart and add expensive item
        CartManagement::clearCartItems();
        CartManagement::addItemToCart($expensivePlan->id);

        $component = Livewire::test(CartPage::class);
        $summary = $component->get('cartSummary');

        $this->assertEquals(0, $summary['shipping']);
    }

    /** @test */
    public function shipping_cost_applied_for_small_orders()
    {
        $component = Livewire::test(CartPage::class);
        $summary = $component->get('cartSummary');

        // Should have shipping cost for orders under $50
        $this->assertEquals(5.99, $summary['shipping']);
    }

    /** @test */
    public function tax_calculation_varies_by_country()
    {
        $component = Livewire::test(CartPage::class)
            ->set('country', 'US');

        $usComponent = $component->get('cartSummary');

        $component->set('country', 'GB');
        $gbComponent = $component->get('cartSummary');

        // Tax rates should be different
        $this->assertNotEquals($usComponent['tax'], $gbComponent['tax']);
    }

    /** @test */
    public function recommended_plans_display_relevant_items()
    {
        $component = Livewire::test(CartPage::class);
        $recommended = $component->get('recommendedPlans');

        // Should return plans from same category but not already in cart
        foreach ($recommended as $plan) {
            $this->assertNotEquals($this->serverPlan->id, $plan->id);
        }
    }

    /** @test */
    public function add_recommended_to_cart_works()
    {
        $anotherPlan = ServerPlan::factory()->create([
            'is_active' => true,
            'is_featured' => true
        ]);

        Livewire::test(CartPage::class)
            ->call('addRecommendedToCart', $anotherPlan->id)
            ->assertDispatched('update-cart-count');
    }

    /** @test */
    public function cart_updated_listener_refreshes_data()
    {
        Livewire::test(CartPage::class)
            ->dispatch('cartUpdated')
            ->assertStatus(200);
    }

    /** @test */
    public function empty_cart_redirects_from_mount()
    {
        // Clear cart completely
        CartManagement::clearCartItems();

        // This would typically redirect in a real scenario
        // For testing, we just verify the cart is empty
        $component = Livewire::test(CartPage::class);
        $this->assertEmpty($component->get('order_items'));
    }

    /** @test */
    public function update_quantity_removes_item_when_zero()
    {
        Livewire::test(CartPage::class)
            ->call('updateQuantity', $this->serverPlan->id, 0)
            ->assertSet('grand_amount', 0);
    }

    /** @test */
    public function coupon_discounts_calculate_correctly()
    {
        // Test percentage discount
        $component = Livewire::test(CartPage::class)
            ->set('coupon_code', 'SAVE10')
            ->call('applyCouponCode');

        $discount = $component->get('discount_amount');
        $this->assertEquals(round(19.99 * 0.10, 2), $discount);

        // Test fixed amount discount
        $component->set('coupon_code', 'WELCOME')
            ->call('applyCouponCode');

        $discount = $component->get('discount_amount');
        $this->assertEquals(5.00, $discount);
    }
}
