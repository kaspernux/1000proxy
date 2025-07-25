<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\ProductDetailPage;
use App\Models\ServerPlan;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    protected $serverPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestData();
    }

    private function createTestData()
    {
        // Create server brand and category
        $brand = ServerBrand::factory()->create(['name' => 'Test Brand']);
        $category = ServerCategory::factory()->create(['name' => 'Test Category']);

        // Create server
        $server = Server::factory()->create([
            'name' => 'Test Server',
            'country' => 'US',
            'status' => 'up',
            'server_brand_id' => $brand->id
        ]);

        // Create server plan
        $this->serverPlan = ServerPlan::factory()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 9.99,
            'is_active' => true,
            'server_id' => $server->id,
            'server_brand_id' => $brand->id,
            'server_category_id' => $category->id
        ]);
    }

    /** @test */
    public function product_detail_page_renders_successfully()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertStatus(200)
            ->assertSee($this->serverPlan->name)
            ->assertViewIs('livewire.product-detail-page');
    }

    /** @test */
    public function component_loads_correct_server_plan()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertViewHas('serverPlan', function ($plan) {
                return $plan->id === $this->serverPlan->id;
            });
    }

    /** @test */
    public function quantity_increment_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertSet('quantity', 1)
            ->call('increaseQty')
            ->assertSet('quantity', 2)
            ->call('increaseQty')
            ->assertSet('quantity', 3);
    }

    /** @test */
    public function quantity_decrement_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->set('quantity', 3)
            ->call('decreaseQty')
            ->assertSet('quantity', 2)
            ->call('decreaseQty')
            ->assertSet('quantity', 1);
    }

    /** @test */
    public function quantity_cannot_go_below_one()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertSet('quantity', 1)
            ->call('decreaseQty')
            ->assertSet('quantity', 1);
    }

    /** @test */
    public function quantity_cannot_exceed_maximum()
    {
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->set('quantity', 10);

        $component->call('increaseQty')
            ->assertSet('quantity', 10); // Should stay at max
    }

    /** @test */
    public function duration_update_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertSet('selectedDuration', 1)
            ->call('updateDuration', 6)
            ->assertSet('selectedDuration', 6)
            ->call('updateDuration', 12)
            ->assertSet('selectedDuration', 12);
    }

    /** @test */
    public function total_price_calculation_is_correct()
    {
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->set('quantity', 2)
            ->set('selectedDuration', 3);

        // Base: 9.99 * 2 * 3 = 59.94, with 5% discount = 56.94
        $expectedTotal = round(9.99 * 2 * 3 * 0.95, 2);

        $this->assertEquals($expectedTotal, $component->get('totalPrice'));
    }

    /** @test */
    public function total_price_applies_duration_discounts()
    {
        // Test 3-month discount (5%)
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->set('selectedDuration', 3);
        $expected3Month = round(9.99 * 3 * 0.95, 2);
        $this->assertEquals($expected3Month, $component->get('totalPrice'));

        // Test 6-month discount (10%)
        $component->set('selectedDuration', 6);
        $expected6Month = round(9.99 * 6 * 0.9, 2);
        $this->assertEquals($expected6Month, $component->get('totalPrice'));

        // Test 12-month discount (20%)
        $component->set('selectedDuration', 12);
        $expected12Month = round(9.99 * 12 * 0.8, 2);
        $this->assertEquals($expected12Month, $component->get('totalPrice'));
    }

    /** @test */
    public function add_to_cart_works_for_active_plan()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->call('addToCart', $this->serverPlan->id)
            ->assertDispatched('update-cart-count')
            ->assertDispatched('cartUpdated');
    }

    /** @test */
    public function add_to_cart_fails_for_inactive_plan()
    {
        $this->serverPlan->update(['is_active' => false]);

        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->call('addToCart', $this->serverPlan->id)
            ->assertNotDispatched('cartUpdated');
    }

    /** @test */
    public function server_status_check_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->call('checkServerStatus')
            ->assertSet('serverStatus', 'up')
            ->assertNotNull('serverHealth');
    }

    /** @test */
    public function specifications_toggle_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertSet('showSpecifications', true)
            ->call('toggleSpecifications')
            ->assertSet('showSpecifications', false)
            ->call('toggleSpecifications')
            ->assertSet('showSpecifications', true);
    }

    /** @test */
    public function active_tab_switching_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->assertSet('activeTab', 'overview')
            ->call('setActiveTab', 'specifications')
            ->assertSet('activeTab', 'specifications')
            ->call('setActiveTab', 'server-details')
            ->assertSet('activeTab', 'server-details');
    }

    /** @test */
    public function component_caches_server_plan_data()
    {
        Cache::flush();

        // First load should cache the data
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug]);

        // Verify cache exists
        $this->assertTrue(Cache::has("product.{$this->serverPlan->slug}"));
    }

    /** @test */
    public function server_status_updated_listener_works()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->dispatch('serverStatusUpdated', [
                'status' => 'down',
                'health' => 45
            ])
            ->assertSet('serverStatus', 'down')
            ->assertSet('serverHealth', 45);
    }

    /** @test */
    public function buy_now_redirects_to_checkout()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->call('buyNow', $this->serverPlan->id)
            ->assertRedirect(route('checkout'));
    }

    /** @test */
    public function share_plan_dispatches_correct_event()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->call('sharePlan', 'twitter')
            ->assertDispatched('openUrl');
    }

    /** @test */
    public function component_handles_nonexistent_plan()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ProductDetailPage::class, ['slug' => 'nonexistent-plan']);
    }

    /** @test */
    public function cart_updated_listener_refreshes_component()
    {
        Livewire::test(ProductDetailPage::class, ['slug' => $this->serverPlan->slug])
            ->dispatch('cartUpdated')
            ->assertStatus(200);
    }
}
