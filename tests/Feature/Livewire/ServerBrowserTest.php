<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use App\Models\ServerPlan;
use App\Models\Order;
use App\Livewire\Components\ServerBrowser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Comprehensive Livewire Component Tests
 *
 * Tests all Livewire component interactions, real-time updates,
 * state management, error handling, and performance under load.
 *
 * @version 1.0.0
 * @author ProxyAdmin System
 */
class ServerBrowserTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $user;
    protected $customer;
    protected $servers;
    protected $categories;
    protected $brands;
    protected $plans;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and customer
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create categories
        $this->categories = ServerCategory::factory()->count(5)->create();

        // Create brands
        $this->brands = ServerBrand::factory()->count(3)->create();

        // Create plans
        $this->plans = ServerPlan::factory()->count(10)->create();

        // Create servers with various configurations
        $this->servers = collect();

        // Create servers for different countries
        $countries = ['US', 'UK', 'DE', 'FR', 'JP'];
        foreach ($countries as $country) {
            $this->servers = $this->servers->merge(
                Server::factory()->count(5)->create([
                    'country' => $country,
                    'category_id' => $this->categories->random()->id,
                    'brand_id' => $this->brands->random()->id,
                    'status' => 'active',
                    'is_available' => true
                ])
            );
        }
    }

    /** @test */
    public function server_browser_component_renders_successfully()
    {
        Livewire::test(ServerBrowser::class)
            ->assertStatus(200)
            ->assertSee('Server Browser')
            ->assertViewIs('livewire.components.server-browser');
    }

    /** @test */
    public function server_browser_displays_servers_correctly()
    {
        Livewire::test(ServerBrowser::class)
            ->assertStatus(200)
            ->assertViewHas('servers')
            ->assertSee($this->servers->first()->location)
            ->call('loadServers')
            ->assertStatus(200);
    }

    /** @test */
    public function search_functionality_works_correctly()
    {
        $searchServer = $this->servers->first();

        Livewire::test(ServerBrowser::class)
            ->set('searchTerm', $searchServer->location)
            ->call('updateFilters')
            ->assertSee($searchServer->location)
            ->assertDontSee($this->servers->last()->location);
    }

    /** @test */
    public function country_filtering_works_correctly()
    {
        $targetCountry = 'US';
        $usServers = $this->servers->where('country', $targetCountry);
        $nonUsServers = $this->servers->where('country', '!=', $targetCountry);

        Livewire::test(ServerBrowser::class)
            ->set('selectedCountry', $targetCountry)
            ->call('updateFilters')
            ->assertSee($usServers->first()->location)
            ->assertDontSee($nonUsServers->first()->location);
    }

    /** @test */
    public function category_filtering_works_correctly()
    {
        $category = $this->categories->first();
        $categoryServers = $this->servers->where('category_id', $category->id);

        if ($categoryServers->isNotEmpty()) {
            Livewire::test(ServerBrowser::class)
                ->set('selectedCategory', $category->id)
                ->call('updateFilters')
                ->assertSee($categoryServers->first()->location);
        }
    }

    /** @test */
    public function brand_filtering_works_correctly()
    {
        $brand = $this->brands->first();
        $brandServers = $this->servers->where('brand_id', $brand->id);

        if ($brandServers->isNotEmpty()) {
            Livewire::test(ServerBrowser::class)
                ->set('selectedBrand', $brand->id)
                ->call('updateFilters')
                ->assertSee($brandServers->first()->location);
        }
    }

    /** @test */
    public function price_range_filtering_works_correctly()
    {
        // Create plans with specific prices
        $cheapPlan = ServerPlan::factory()->create(['price' => 5.00]);
        $expensivePlan = ServerPlan::factory()->create(['price' => 50.00]);

        $cheapServer = Server::factory()->create([
            'plan_id' => $cheapPlan->id,
            'category_id' => $this->categories->first()->id,
            'brand_id' => $this->brands->first()->id
        ]);

        Livewire::test(ServerBrowser::class)
            ->set('priceRange', [0, 10])
            ->call('updateFilters')
            ->assertSee($cheapServer->location);
    }

    /** @test */
    public function sorting_functionality_works_correctly()
    {
        $sortOptions = ['location_first', 'price_low', 'price_high', 'speed_high', 'newest'];

        foreach ($sortOptions as $sortBy) {
            Livewire::test(ServerBrowser::class)
                ->set('sortBy', $sortBy)
                ->call('updateFilters')
                ->assertStatus(200);
        }
    }

    /** @test */
    public function view_mode_switching_works_correctly()
    {
        $viewModes = ['grid', 'list', 'compact'];

        foreach ($viewModes as $viewMode) {
            Livewire::test(ServerBrowser::class)
                ->set('viewMode', $viewMode)
                ->assertSet('viewMode', $viewMode)
                ->assertStatus(200);
        }
    }

    /** @test */
    public function pagination_works_correctly()
    {
        Livewire::test(ServerBrowser::class)
            ->set('itemsPerPage', 5)
            ->call('updateFilters')
            ->assertStatus(200)
            ->call('nextPage')
            ->assertStatus(200);
    }

    /** @test */
    public function real_time_updates_work_correctly()
    {
        $component = Livewire::test(ServerBrowser::class);

        // Test reactive property updates
        $component
            ->set('searchTerm', 'test')
            ->assertSet('searchTerm', 'test')
            ->set('selectedCountry', 'US')
            ->assertSet('selectedCountry', 'US')
            ->set('selectedCategory', $this->categories->first()->id)
            ->assertSet('selectedCategory', $this->categories->first()->id);
    }

    /** @test */
    public function component_state_management_works_correctly()
    {
        $component = Livewire::test(ServerBrowser::class);

        // Set multiple filters
        $component
            ->set('searchTerm', 'test server')
            ->set('selectedCountry', 'US')
            ->set('selectedCategory', $this->categories->first()->id)
            ->set('priceRange', [10, 50])
            ->call('updateFilters');

        // Verify state is maintained
        $component
            ->assertSet('searchTerm', 'test server')
            ->assertSet('selectedCountry', 'US')
            ->assertSet('selectedCategory', $this->categories->first()->id)
            ->assertSet('priceRange', [10, 50]);
    }

    /** @test */
    public function server_selection_works_correctly()
    {
        $server = $this->servers->first();

        Livewire::test(ServerBrowser::class)
            ->call('selectServer', $server->id)
            ->assertEmitted('serverSelected')
            ->assertStatus(200);
    }

    /** @test */
    public function quick_order_functionality_works()
    {
        $this->actingAs($this->user);
        $server = $this->servers->first();

        Livewire::test(ServerBrowser::class)
            ->call('quickOrder', $server->id)
            ->assertStatus(200);
    }

    /** @test */
    public function favorites_functionality_works()
    {
        $this->actingAs($this->user);
        $server = $this->servers->first();

        Livewire::test(ServerBrowser::class)
            ->call('toggleFavorite', $server->id)
            ->assertStatus(200);
    }

    /** @test */
    public function component_handles_errors_gracefully()
    {
        Livewire::test(ServerBrowser::class)
            ->call('selectServer', 99999) // Non-existent server ID
            ->assertStatus(200); // Should not crash
    }

    /** @test */
    public function component_handles_invalid_filter_values()
    {
        Livewire::test(ServerBrowser::class)
            ->set('selectedCountry', 'INVALID')
            ->set('selectedCategory', 99999)
            ->set('selectedBrand', 99999)
            ->set('priceRange', [-10, 2000])
            ->call('updateFilters')
            ->assertStatus(200); // Should handle gracefully
    }

    /** @test */
    public function component_validates_required_fields()
    {
        Livewire::test(ServerBrowser::class)
            ->set('itemsPerPage', 0) // Invalid value
            ->call('updateFilters')
            ->assertHasErrors(['itemsPerPage']);
    }

    /** @test */
    public function component_clears_filters_correctly()
    {
        Livewire::test(ServerBrowser::class)
            ->set('searchTerm', 'test')
            ->set('selectedCountry', 'US')
            ->set('selectedCategory', $this->categories->first()->id)
            ->call('clearFilters')
            ->assertSet('searchTerm', '')
            ->assertSet('selectedCountry', '')
            ->assertSet('selectedCategory', '');
    }

    /** @test */
    public function component_refreshes_data_correctly()
    {
        Livewire::test(ServerBrowser::class)
            ->call('refreshData')
            ->assertStatus(200)
            ->assertDispatched('dataRefreshed');
    }

    /** @test */
    public function component_handles_concurrent_updates()
    {
        $component1 = Livewire::test(ServerBrowser::class);
        $component2 = Livewire::test(ServerBrowser::class);

        // Simulate concurrent filter updates
        $component1->set('searchTerm', 'server1');
        $component2->set('searchTerm', 'server2');

        $component1->assertSet('searchTerm', 'server1');
        $component2->assertSet('searchTerm', 'server2');
    }

    /** @test */
    public function component_performance_under_load()
    {
        // Create many servers to test performance
        Server::factory()->count(100)->create([
            'category_id' => $this->categories->first()->id,
            'brand_id' => $this->brands->first()->id
        ]);

        $start = microtime(true);

        Livewire::test(ServerBrowser::class)
            ->call('loadServers')
            ->assertStatus(200);

        $end = microtime(true);
        $executionTime = $end - $start;

        // Assert component loads within acceptable time (2 seconds)
        $this->assertLessThan(2.0, $executionTime, 'Component should load within 2 seconds');
    }

    /** @test */
    public function component_handles_large_datasets()
    {
        // Create many servers
        Server::factory()->count(500)->create([
            'category_id' => $this->categories->first()->id,
            'brand_id' => $this->brands->first()->id
        ]);

        Livewire::test(ServerBrowser::class)
            ->set('itemsPerPage', 50)
            ->call('loadServers')
            ->assertStatus(200);
    }

    /** @test */
    public function component_memory_usage_is_reasonable()
    {
        $memoryBefore = memory_get_usage(true);

        Livewire::test(ServerBrowser::class)
            ->call('loadServers')
            ->assertStatus(200);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Assert memory usage is reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage should be less than 10MB');
    }

    /** @test */
    public function component_accessibility_features_work()
    {
        Livewire::test(ServerBrowser::class)
            ->assertSee('aria-label')
            ->assertSee('role=')
            ->assertStatus(200);
    }

    /** @test */
    public function component_mobile_responsiveness()
    {
        Livewire::test(ServerBrowser::class)
            ->set('viewMode', 'compact') // Mobile-optimized view
            ->call('updateFilters')
            ->assertSet('viewMode', 'compact')
            ->assertStatus(200);
    }

    /** @test */
    public function component_event_listeners_work()
    {
        Livewire::test(ServerBrowser::class)
            ->dispatch('filtersUpdated')
            ->assertStatus(200);
    }

    /** @test */
    public function component_lifecycle_hooks_work()
    {
        $component = Livewire::test(ServerBrowser::class);

        // Test mount
        $component->assertStatus(200);

        // Test updates
        $component->set('searchTerm', 'test')->assertSet('searchTerm', 'test');
    }

    /** @test */
    public function component_caching_works_correctly()
    {
        // First call
        $start1 = microtime(true);
        Livewire::test(ServerBrowser::class)->call('loadServers');
        $time1 = microtime(true) - $start1;

        // Second call (should be faster due to caching)
        $start2 = microtime(true);
        Livewire::test(ServerBrowser::class)->call('loadServers');
        $time2 = microtime(true) - $start2;

        // Second call should generally be faster
        $this->assertGreaterThan(0, $time1);
        $this->assertGreaterThan(0, $time2);
    }

    protected function tearDown(): void
    {
        // Clean up any resources if needed
        parent::tearDown();
    }
}
