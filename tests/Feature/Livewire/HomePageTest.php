<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\HomePage;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->createTestData();
    }

    private function createTestData()
    {
        // Create server brands
        ServerBrand::factory()->create([
            'name' => 'Premium VPN',
            'is_active' => 1,
            'display_order' => 1
        ]);

        ServerBrand::factory()->create([
            'name' => 'Gaming VPN',
            'is_active' => 1,
            'display_order' => 2
        ]);

        // Create server categories
        ServerCategory::factory()->create([
            'name' => 'Gaming',
            'is_active' => true,
            'display_order' => 1
        ]);

        ServerCategory::factory()->create([
            'name' => 'Streaming',
            'is_active' => true,
            'display_order' => 2
        ]);

        // Create server plans
        ServerPlan::factory()->count(3)->create([
            'is_active' => true,
            'is_featured' => true
        ]);

        // Create test users and orders for stats
        User::factory()->count(10)->create(['role' => 'customer']);
        Order::factory()->count(5)->create(['status' => 'completed']);
    }

    /** @test */
    public function homepage_component_renders_successfully()
    {
        Livewire::test(HomePage::class)
            ->assertStatus(200)
            ->assertSee('1000 PROXIES')
            ->assertViewIs('livewire.home-page');
    }

    /** @test */
    public function homepage_displays_active_brands()
    {
        Livewire::test(HomePage::class)
            ->assertSee('Premium VPN')
            ->assertSee('Gaming VPN');
    }

    /** @test */
    public function homepage_displays_active_categories()
    {
        Livewire::test(HomePage::class)
            ->assertSee('Gaming')
            ->assertSee('Streaming');
    }

    /** @test */
    public function homepage_displays_featured_plans()
    {
        $featuredPlan = ServerPlan::where('is_featured', true)->first();

        Livewire::test(HomePage::class)
            ->assertSee($featuredPlan->name);
    }

    /** @test */
    public function homepage_displays_platform_stats()
    {
        Livewire::test(HomePage::class)
            ->assertViewHas('stats')
            ->assertSee('10') // Total users
            ->assertSee('5');  // Total orders
    }

    /** @test */
    public function search_functionality_works()
    {
        Livewire::test(HomePage::class)
            ->set('searchTerm', 'gaming')
            ->call('searchPlans')
            ->assertRedirect('/products?search=gaming');
    }

    /** @test */
    public function category_selection_redirects_to_products()
    {
        $category = ServerCategory::first();

        Livewire::test(HomePage::class)
            ->call('selectCategory', $category->id)
            ->assertRedirect('/products?category=' . $category->id);
    }

    /** @test */
    public function brand_selection_redirects_to_products()
    {
        $brand = ServerBrand::first();

        Livewire::test(HomePage::class)
            ->call('selectBrand', $brand->id)
            ->assertRedirect('/products?brand=' . $brand->id);
    }

    /** @test */
    public function add_to_cart_functionality_works()
    {
        $plan = ServerPlan::first();

        Livewire::test(HomePage::class)
            ->call('addToCart', $plan->id)
            ->assertDispatched('cartUpdated')
            ->assertDispatched('toast');
    }

    /** @test */
    public function add_to_cart_fails_for_inactive_plan()
    {
        $plan = ServerPlan::factory()->create(['is_active' => false]);

        Livewire::test(HomePage::class)
            ->call('addToCart', $plan->id)
            ->assertDispatched('toast', function ($event) {
                return $event['type'] === 'error';
            });
    }

    /** @test */
    public function stats_toggle_functionality_works()
    {
        Livewire::test(HomePage::class)
            ->assertSet('showStats', true)
            ->call('toggleStats')
            ->assertSet('showStats', false)
            ->call('toggleStats')
            ->assertSet('showStats', true);
    }

    /** @test */
    public function featured_plans_toggle_functionality_works()
    {
        Livewire::test(HomePage::class)
            ->assertSet('showFeaturedPlans', true)
            ->call('toggleFeaturedPlans')
            ->assertSet('showFeaturedPlans', false);
    }

    /** @test */
    public function cached_data_improves_performance()
    {
        // Clear cache first
        Cache::flush();

        // First load should cache the data
        Livewire::test(HomePage::class);

        // Verify cache exists
        $this->assertTrue(Cache::has('homepage.brands'));
        $this->assertTrue(Cache::has('homepage.categories'));
        $this->assertTrue(Cache::has('homepage.featured_plans'));
        $this->assertTrue(Cache::has('homepage.platform_stats'));
    }

    /** @test */
    public function search_term_update_dispatches_event()
    {
        Livewire::test(HomePage::class)
            ->set('searchTerm', 'test search')
            ->assertDispatched('searchUpdated', 'test search');
    }

    /** @test */
    public function user_registration_event_refreshes_stats()
    {
        $component = Livewire::test(HomePage::class);

        // Simulate user registration event
        $component->call('handleUserRegistered', 123)
            ->assertSet('showStats', true);

        // Verify cache was cleared
        $this->assertFalse(Cache::has('homepage.platform_stats'));
    }

    /** @test */
    public function component_handles_empty_data_gracefully()
    {
        // Clear all test data
        ServerBrand::query()->delete();
        ServerCategory::query()->delete();
        ServerPlan::query()->delete();

        Livewire::test(HomePage::class)
            ->assertStatus(200)
            ->assertDontSee('Premium VPN');
    }

    /** @test */
    public function complex_search_parameters_redirect_correctly()
    {
        Livewire::test(HomePage::class)
            ->set('searchTerm', 'premium')
            ->set('selectedCategory', '1')
            ->set('selectedBrand', '2')
            ->call('searchPlans')
            ->assertRedirect('/products?search=premium&category=1&brand=2');
    }

    /** @test */
    public function cart_updated_listener_refreshes_component()
    {
        Livewire::test(HomePage::class)
            ->dispatch('cartUpdated')
            ->assertStatus(200); // Component should refresh without errors
    }
}
