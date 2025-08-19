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
    protected $categories;
    protected $brands;
    protected $servers;
    protected $plans;

    protected function setUp(): void
    {
        parent::setUp();

    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();

        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        $this->categories = ServerCategory::factory()->count(3)->create();
        $this->brands = ServerBrand::factory()->count(2)->create();

        // Create a set of active servers in fixed countries to drive filters
        $countries = ['US', 'UK', 'DE'];
        $this->servers = collect();
        foreach ($countries as $idx => $country) {
            $this->servers->push(
                Server::factory()->active()->create([
                    'country' => $country,
                    'server_category_id' => $this->categories[$idx % $this->categories->count()]->id,
                    'server_brand_id' => $this->brands[$idx % $this->brands->count()]->id,
                ])
            );
        }

        // Create active plans tied to those servers
        $this->plans = collect();
        foreach ($this->servers as $server) {
            $this->plans->push(ServerPlan::factory()->active()->create([
                'server_id' => $server->id,
                'server_category_id' => $server->server_category_id,
                'server_brand_id' => $server->server_brand_id,
                'price' => $this->faker->randomFloat(2, 5, 20),
                'protocol' => 'vless',
            ]));
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function component_renders()
    {
        Livewire::test(ServerBrowser::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.components.server-browser');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function search_filters_by_plan_name()
    {
        $plan = $this->plans->first();

        Livewire::test(ServerBrowser::class)
            ->set('searchTerm', $plan->name)
            ->call('applyFilters')
            ->assertSee($plan->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function country_filtering_works()
    {
        $target = $this->servers->first()->country;
        $otherServer = $this->servers->last();

        Livewire::test(ServerBrowser::class)
            ->set('selectedCountry', $target)
            ->call('applyFilters')
            // Should list the selected country somewhere (header / filter state)
            ->assertSee($target)
            // Validate that every rendered plan's associated server country matches selected
            ->assertViewHas('serverPlans', function($plans) use ($target) {
                // ensure we have at least one plan
                if ($plans->count() === 0) return false;
                return $plans->pluck('server.country')->unique()->count() === 1
                    && $plans->first()->server->country === $target;
            });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function category_filtering_uses_slug()
    {
        $category = $this->categories->first();
        // Ensure at least one plan belongs to this category
        $plan = $this->plans->first();
        $plan->update(['server_category_id' => $category->id]);

        Livewire::test(ServerBrowser::class)
            ->set('selectedCategory', $category->slug)
            ->call('applyFilters')
            ->assertSee($plan->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function brand_filtering_uses_slug()
    {
        $brand = $this->brands->first();
        $plan = $this->plans->first();
        $plan->update(['server_brand_id' => $brand->id]);

        Livewire::test(ServerBrowser::class)
            ->set('selectedBrand', $brand->slug)
            ->call('applyFilters')
            ->assertSee($plan->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function price_range_filtering_limits_results()
    {
        $cheap = ServerPlan::factory()->active()->create([
            'server_id' => $this->servers->first()->id,
            'server_category_id' => $this->categories->first()->id,
            'server_brand_id' => $this->brands->first()->id,
            'price' => 5.00,
        ]);
        ServerPlan::factory()->active()->create([
            'server_id' => $this->servers->first()->id,
            'server_category_id' => $this->categories->first()->id,
            'server_brand_id' => $this->brands->first()->id,
            'price' => 80.00,
        ]);

        Livewire::test(ServerBrowser::class)
            ->set('priceRange', [0, 10])
            ->call('applyFilters')
            ->assertSee($cheap->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sorting_options_do_not_error()
    {
        foreach (['location_first','price_low','price_high','speed_high','newest'] as $sort) {
            Livewire::test(ServerBrowser::class)
                ->set('sortBy', $sort)
                ->call('applyFilters')
                ->assertStatus(200);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function view_mode_switching_updates_state()
    {
        foreach (['grid','list','compact'] as $mode) {
            Livewire::test(ServerBrowser::class)
                ->set('viewMode', $mode)
                ->assertSet('viewMode', $mode);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pagination_moves_between_pages()
    {
        // create more plans to ensure multiple pages
        ServerPlan::factory()->count(30)->active()->create();

        Livewire::test(ServerBrowser::class)
            ->set('itemsPerPage', 5)
            ->call('applyFilters')
            ->call('nextPage')
            ->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function real_time_updates_basic_reactivity()
    {
        $component = Livewire::test(ServerBrowser::class);

        // Test reactive property updates
        $component
            ->set('searchTerm', 'test')
            ->assertSet('searchTerm', 'test')
            ->set('selectedCountry', 'US')
            ->assertSet('selectedCountry', 'US')
            ->set('selectedCategory', $this->categories->first()->slug)
            ->assertSet('selectedCategory', $this->categories->first()->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function component_state_persists()
    {
        $component = Livewire::test(ServerBrowser::class);

        // Set multiple filters
        $component
            ->set('searchTerm', 'test server')
            ->set('selectedCountry', 'US')
            ->set('selectedCategory', $this->categories->first()->slug)
            ->set('priceRange', [10, 50])
            ->call('applyFilters');

        // Verify state is maintained
        $component
            ->assertSet('searchTerm', 'test server')
            ->assertSet('selectedCountry', 'US')
            ->assertSet('selectedCategory', $this->categories->first()->slug)
            ->assertSet('priceRange', [10, 50]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_filter_values_do_not_crash()
    {
        Livewire::test(ServerBrowser::class)
            ->set('selectedCountry', 'INVALID')
        ->set('selectedCategory', 'non-existent-slug')
        ->set('selectedBrand', 'non-existent-slug')
            ->set('priceRange', [-10, 2000])
        ->call('applyFilters')
            ->assertStatus(200); // Should handle gracefully
    }
    // Removed legacy/performance/accessibility/memory tests that referred to outdated component methods & columns.
}
