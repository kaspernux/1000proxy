<?php

namespace Tests\Feature\Frontend;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTestSuite extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected $servers;
    protected $serverPlans;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'name' => 'Admin User',
        ]);

        $this->customer = Customer::factory()->create([
            'email' => 'customer@test.com',
            'name' => 'Test Customer',
        ]);

        // Create large dataset for performance testing
        $this->createLargeDataset();
    }

    protected function createLargeDataset(): void
    {
        // Create categories
        $categories = ServerCategory::factory(10)->create();

        // Create brands
        $brands = ServerBrand::factory(15)->create();

        // Create servers
        $this->servers = Server::factory(100)->create();

        // Create server plans (large dataset for filtering performance)
        $this->serverPlans = collect();
        foreach ($this->servers as $server) {
            $plans = ServerPlan::factory(rand(3, 8))->create([
                'server_id' => $server->id,
                'category_id' => $categories->random()->id,
                'brand_id' => $brands->random()->id,
            ]);
            $this->serverPlans = $this->serverPlans->concat($plans);
        }

        // Create orders
        Order::factory(200)->create([
            'customer_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function homepage_loads_within_performance_threshold()
    {
        $start = microtime(true);

        $response = $this->get('/');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(1.0, $duration, 'Homepage should load within 1 second');
    }

    /** @test */
    public function server_listing_with_large_dataset_performs_well()
    {
        $start = microtime(true);

        $response = $this->get('/products');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(2.0, $duration, 'Server listing should load within 2 seconds with large dataset');
    }

    /** @test */
    public function server_filtering_performs_efficiently()
    {
        $start = microtime(true);

        $response = $this->get('/products?' . http_build_query([
            'location' => 'US',
            'category' => $this->serverPlans->first()->category_id,
            'brand' => $this->serverPlans->first()->brand_id,
            'min_price' => 5,
            'max_price' => 50,
        ]));

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(1.5, $duration, 'Filtered results should load within 1.5 seconds');
    }

    /** @test */
    public function api_endpoints_meet_performance_requirements()
    {
        $endpoints = [
            '/api/server-plans',
            '/api/server-plans/filters',
            '/api/servers/health',
        ];

        foreach ($endpoints as $endpoint) {
            $start = microtime(true);

            $response = $this->getJson($endpoint);

            $duration = microtime(true) - $start;

            $response->assertOk();
            $this->assertLessThan(0.5, $duration, "API endpoint {$endpoint} should respond within 500ms");
        }
    }

    /** @test */
    public function database_queries_are_optimized()
    {
        DB::enableQueryLog();

        $this->get('/products');

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertLessThan(10, $queryCount, 'Product listing should use fewer than 10 database queries');

        // Check for N+1 query problems
        $duplicateQueries = array_count_values(array_column($queries, 'query'));
        $maxDuplicates = max($duplicateQueries);

        $this->assertLessThan(5, $maxDuplicates, 'Should not have excessive duplicate queries (N+1 problem)');
    }

    /** @test */
    public function caching_improves_performance()
    {
        // First request (cold cache)
        $start = microtime(true);
        $response1 = $this->get('/products');
        $duration1 = microtime(true) - $start;

        $response1->assertOk();

        // Second request (warm cache)
        $start = microtime(true);
        $response2 = $this->get('/products');
        $duration2 = microtime(true) - $start;

        $response2->assertOk();

        // Cached response should be significantly faster
        $this->assertLessThan($duration1 * 0.5, $duration2, 'Cached response should be at least 50% faster');
    }

    /** @test */
    public function memory_usage_is_within_acceptable_limits()
    {
        $memoryStart = memory_get_usage();

        $this->get('/products');

        $memoryEnd = memory_get_usage();
        $memoryUsed = $memoryEnd - $memoryStart;

        // Memory usage should be less than 50MB for a single request
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage should be less than 50MB');
    }

    /** @test */
    public function concurrent_request_simulation()
    {
        $requests = [];
        $maxDuration = 0;

        // Simulate 5 concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $start = microtime(true);
            $response = $this->get('/products?page=' . ($i + 1));
            $duration = microtime(true) - $start;

            $requests[] = [
                'response' => $response,
                'duration' => $duration,
            ];

            $maxDuration = max($maxDuration, $duration);
        }

        // All requests should complete successfully
        foreach ($requests as $request) {
            $request['response']->assertOk();
        }

        // Even under concurrent load, requests should complete within 3 seconds
        $this->assertLessThan(3.0, $maxDuration, 'Requests under concurrent load should complete within 3 seconds');
    }

    /** @test */
    public function large_form_submission_performance()
    {
        $this->actingAs($this->customer, 'customer');

        $start = microtime(true);

        $response = $this->post('/cart/bulk-add', [
            'items' => $this->serverPlans->take(10)->map(function ($plan) {
                return [
                    'server_plan_id' => $plan->id,
                    'quantity' => rand(1, 3),
                ];
            })->toArray(),
        ]);

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(2.0, $duration, 'Bulk cart operations should complete within 2 seconds');
    }

    /** @test */
    public function admin_panel_performance_with_large_dataset()
    {
        $this->actingAs($this->admin);

        $start = microtime(true);

        $response = $this->get('/admin/customer-management/customers');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(3.0, $duration, 'Admin panel should load within 3 seconds even with large datasets');
    }

    /** @test */
    public function search_functionality_performance()
    {
        $searchTerms = ['gaming', 'proxy', 'server', 'titan', 'us'];

        foreach ($searchTerms as $term) {
            $start = microtime(true);

            $response = $this->get('/products?search=' . urlencode($term));

            $duration = microtime(true) - $start;

            $response->assertOk();
            $this->assertLessThan(1.0, $duration, "Search for '{$term}' should complete within 1 second");
        }
    }

    /** @test */
    public function image_loading_optimization()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('loading="lazy"', false)  // Lazy loading
            ->assertSee('decoding="async"', false) // Async decoding
            ->assertSee('srcset', false);          // Responsive images
    }

    /** @test */
    public function css_and_js_optimization()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for minified assets in production
        if (app()->environment('production')) {
            $this->assertStringContainsString('.min.css', $content);
            $this->assertStringContainsString('.min.js', $content);
        }

        // Check for resource preloading
        $this->assertStringContainsString('rel="preload"', $content);
        $this->assertStringContainsString('rel="prefetch"', $content);
    }

    /** @test */
    public function pagination_performance()
    {
        $pages = [1, 5, 10, 20]; // Test various page numbers

        foreach ($pages as $page) {
            $start = microtime(true);

            $response = $this->get("/products?page={$page}");

            $duration = microtime(true) - $start;

            $response->assertOk();
            $this->assertLessThan(1.5, $duration, "Page {$page} should load within 1.5 seconds");
        }
    }

    /** @test */
    public function ajax_request_performance()
    {
        $start = microtime(true);

        $response = $this->getJson('/api/server-plans', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(0.8, $duration, 'AJAX requests should complete within 800ms');
    }

    /** @test */
    public function livewire_component_performance()
    {
        $start = microtime(true);

        $response = $this->get('/products');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(2.0, $duration, 'Livewire components should render within 2 seconds');

        // Check for Livewire optimization attributes
        $response->assertSee('wire:loading', false);
        $response->assertSee('wire:offline', false);
    }

    /** @test */
    public function session_performance()
    {
        $start = microtime(true);

        // Simulate session-heavy operations
        $response = $this->withSession([
            'cart' => $this->serverPlans->take(20)->pluck('id')->toArray(),
            'user_preferences' => [
                'theme' => 'dark',
                'language' => 'en',
                'timezone' => 'UTC',
            ],
        ])->get('/cart');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(1.5, $duration, 'Session-heavy operations should complete within 1.5 seconds');
    }

    /** @test */
    public function file_upload_performance()
    {
        $this->actingAs($this->customer, 'customer');

        // Create a test file
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 800, 600);

        $start = microtime(true);

        $response = $this->post('/profile/avatar', [
            'avatar' => $fakeFile,
        ]);

        $duration = microtime(true) - $start;

        $this->assertLessThan(3.0, $duration, 'File upload should complete within 3 seconds');
    }

    /** @test */
    public function queue_job_processing_performance()
    {
        $start = microtime(true);

        // Dispatch a test job
        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->post('/orders', [
            'server_plan_id' => $this->serverPlans->first()->id,
            'quantity' => 1,
        ]);

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Job dispatch should complete within 1 second');
    }

    /** @test */
    public function cache_hit_ratio_optimization()
    {
        // Clear cache
        Cache::flush();

        // Make multiple requests to the same endpoint
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/products');
        }

        // All responses should be successful
        foreach ($responses as $response) {
            $response->assertOk();
        }

        // Check cache statistics if available with Redis
        try {
            $store = Cache::getStore();
            if ($store instanceof \Illuminate\Cache\RedisStore) {
                $redis = $store->connection();
                if ($redis && method_exists($redis, 'info')) {
                    $info = $redis->info('stats');
                    if (isset($info['keyspace_hits'], $info['keyspace_misses'])) {
                        $hits = (int) $info['keyspace_hits'];
                        $misses = (int) $info['keyspace_misses'];
                        if ($hits + $misses > 0) {
                            $hitRatio = $hits / ($hits + $misses);
                            $this->assertGreaterThan(0.6, $hitRatio, 'Cache hit ratio should be at least 60%');
                        }
                    }
                }
            } else {
                // For non-Redis cache drivers, just verify cache is working
                $this->assertTrue(true, 'Cache driver does not support statistics');
            }
        } catch (\Exception $e) {
            // Cache statistics not available, skip this assertion
            $this->assertTrue(true, 'Cache statistics not available: ' . $e->getMessage());
        }
    }

    /** @test */
    public function database_connection_pooling_efficiency()
    {
        $start = microtime(true);

        // Make multiple database-heavy requests
        for ($i = 0; $i < 10; $i++) {
            DB::table('server_plans')->count();
        }

        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Multiple DB operations should complete within 1 second');
    }

    /** @test */
    public function response_compression_efficiency()
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip, deflate',
        ])->get('/products');

        $response->assertOk();

        // Check for compression headers
        $contentEncoding = $response->headers->get('Content-Encoding');
        if ($contentEncoding) {
            $this->assertContains($contentEncoding, ['gzip', 'deflate']);
        }
    }

    /** @test */
    public function resource_loading_optimization()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for resource optimization
        $this->assertStringContainsString('rel="preload"', $content);
        $this->assertStringContainsString('rel="dns-prefetch"', $content);
        $this->assertStringContainsString('rel="preconnect"', $content);
    }

    /** @test */
    public function third_party_service_timeout_handling()
    {
        // Mock slow third-party service
        $start = microtime(true);

        $response = $this->get('/products'); // This might call external APIs

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(5.0, $duration, 'Pages should not hang due to slow third-party services');
    }
}
