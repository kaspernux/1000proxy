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
use Tests\TestCase;

class MobileDeviceTestSuite extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected ServerCategory $category;
    protected ServerBrand $brand;
    protected Server $server;
    protected ServerPlan $serverPlan;

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

        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create server categories
        $this->category = ServerCategory::create([
            'name' => 'Gaming',
            'description' => 'Gaming servers',
            'is_active' => true,
        ]);

        // Create server brands
        $this->brand = ServerBrand::create([
            'name' => 'ProxyTitan',
            'description' => 'Premium proxy provider',
            'is_active' => true,
        ]);

        // Create server
        $this->server = Server::create([
            'name' => 'Test Server',
            'location' => 'US',
            'ip_address' => '192.168.1.100',
            'panel_port' => '2053',
            'panel_username' => 'admin',
            'panel_password' => 'password',
            'is_active' => true,
        ]);

        // Create server plan
        $this->serverPlan = ServerPlan::create([
            'server_id' => $this->server->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Gaming Pro',
            'price' => 9.99,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function iphone_user_agent_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        ])->get('/');

        $response->assertOk()
            ->assertSee('viewport', false)
            ->assertSee('mobile-friendly', false)
            ->assertSee('touch-action', false);
    }

    /** @test */
    public function android_user_agent_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36'
        ])->get('/');

        $response->assertOk()
            ->assertSee('viewport', false)
            ->assertSee('mobile', false)
            ->assertSee('responsive', false);
    }

    /** @test */
    public function ipad_tablet_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        ])->get('/');

        $response->assertOk()
            ->assertSee('tablet', false)
            ->assertSee('responsive', false)
            ->assertSee('touch', false);
    }

    /** @test */
    public function mobile_navigation_is_present()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('mobile-menu', false)
            ->assertSee('hamburger', false)
            ->assertSee('nav-toggle', false)
            ->assertSee('offcanvas', false);
    }

    /** @test */
    public function touch_friendly_button_sizes()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('min-h-', false)    // Minimum height for touch targets
            ->assertSee('py-', false)       // Padding for better touch area
            ->assertSee('px-', false)       // Horizontal padding
            ->assertSee('touch-target', false);
    }

    /** @test */
    public function mobile_form_inputs_are_optimized()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/register');

        $response->assertOk()
            ->assertSee('inputmode=', false)    // Input mode for mobile keyboards
            ->assertSee('autocomplete=', false) // Autocomplete attributes
            ->assertSee('type="email"', false)  // Proper input types
            ->assertSee('type="tel"', false);   // Phone number inputs
    }

    /** @test */
    public function mobile_checkout_process_is_streamlined()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/checkout');

        $response->assertOk()
            ->assertSee('mobile-checkout', false)
            ->assertSee('step-indicator', false)
            ->assertSee('progress', false)
            ->assertSee('touch-friendly', false);
    }

    /** @test */
    public function mobile_product_cards_are_responsive()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('grid-cols-1', false)     // Single column on mobile
            ->assertSee('sm:grid-cols-2', false)  // Two columns on small screens
            ->assertSee('lg:grid-cols-3', false)  // Three columns on large screens
            ->assertSee('mobile-card', false);
    }

    /** @test */
    public function mobile_filters_are_collapsible()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('filter-toggle', false)
            ->assertSee('filter-collapse', false)
            ->assertSee('mobile-filter', false)
            ->assertSee('filter-drawer', false);
    }

    /** @test */
    public function mobile_table_responsive_design()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/customer/my-orders');

        $response->assertOk()
            ->assertSee('table-responsive', false)
            ->assertSee('mobile-table', false)
            ->assertSee('card-table', false)     // Table as cards on mobile
            ->assertSee('overflow-x-auto', false);
    }

    /** @test */
    public function mobile_modal_optimization()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('modal-fullscreen', false)  // Full screen modals on mobile
            ->assertSee('modal-bottom', false)      // Bottom sheet modals
            ->assertSee('mobile-modal', false);
    }

    /** @test */
    public function swipe_gestures_support()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('swipe', false)
            ->assertSee('gesture', false)
            ->assertSee('touch-action', false)
            ->assertSee('swiper', false);
    }

    /** @test */
    public function mobile_image_optimization()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('loading="lazy"', false)     // Lazy loading
            ->assertSee('decoding="async"', false)   // Async decoding
            ->assertSee('srcset', false)             // Responsive images
            ->assertSee('sizes', false);             // Image sizes
    }

    /** @test */
    public function mobile_performance_optimization()
    {
        $start = microtime(true);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(2.0, $duration, 'Mobile pages should load within 2 seconds');
    }

    /** @test */
    public function mobile_font_optimization()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('font-display', false)
            ->assertSee('swap', false)
            ->assertSee('preload', false);
    }

    /** @test */
    public function mobile_accessibility_features()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('aria-label', false)
            ->assertSee('role=', false)
            ->assertSee('aria-expanded', false)
            ->assertSee('aria-hidden', false);
    }

    /** @test */
    public function mobile_keyboard_navigation()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('tabindex', false)
            ->assertSee('focus:ring', false)
            ->assertSee('focus:outline', false);
    }

    /** @test */
    public function mobile_zoom_prevention()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $content = $response->getContent();

        // Check viewport meta tag
        $this->assertStringContainsString('name="viewport"', $content);
        $this->assertStringContainsString('user-scalable=no', $content); // For form inputs
        $this->assertStringContainsString('maximum-scale=1', $content);
    }

    /** @test */
    public function mobile_offline_functionality()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('service-worker', false)
            ->assertSee('offline', false)
            ->assertSee('cache', false);
    }

    /** @test */
    public function mobile_push_notification_support()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('notification', false)
            ->assertSee('push', false)
            ->assertSee('fcm', false);
    }

    /** @test */
    public function mobile_app_manifest()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('manifest.json', false)
            ->assertSee('theme-color', false)
            ->assertSee('apple-touch-icon', false);
    }

    /** @test */
    public function mobile_cart_functionality()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->post('/cart/add', [
            'server_plan_id' => $this->serverPlan->id,
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['cart_count', 'total']);
    }

    /** @test */
    public function mobile_payment_integration()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/checkout');

        $response->assertOk()
            ->assertSee('apple-pay', false)
            ->assertSee('google-pay', false)
            ->assertSee('mobile-payment', false);
    }

    /** @test */
    public function mobile_search_functionality()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products?search=gaming');

        $response->assertOk()
            ->assertSee('search-mobile', false)
            ->assertSee('autocomplete', false)
            ->assertSee('search-suggestions', false);
    }

    /** @test */
    public function mobile_error_handling()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/non-existent-page');

        $response->assertStatus(404)
            ->assertSee('mobile-error', false)
            ->assertSee('error-mobile', false);
    }

    /** @test */
    public function mobile_loading_states()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products');

        $response->assertOk()
            ->assertSee('loading-mobile', false)
            ->assertSee('skeleton', false)
            ->assertSee('spinner', false);
    }

    /** @test */
    public function mobile_social_sharing()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/products/' . $this->serverPlan->id);

        $response->assertOk()
            ->assertSee('share-mobile', false)
            ->assertSee('social-share', false)
            ->assertSee('share-api', false);
    }

    /** @test */
    public function mobile_form_validation()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSee('error-mobile', false)
            ->assertSee('validation-mobile', false);
    }

    /** @test */
    public function mobile_analytics_tracking()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('gtag', false)
            ->assertSee('analytics', false)
            ->assertSee('mobile-events', false);
    }

    /** @test */
    public function mobile_session_management()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->withSession(['mobile_user' => true])
            ->get('/');

        $response->assertOk()
            ->assertSessionHas('mobile_user', true);
    }

    /** @test */
    public function mobile_cookie_consent()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('cookie-consent', false)
            ->assertSee('gdpr-mobile', false)
            ->assertSee('privacy-mobile', false);
    }

    /** @test */
    public function mobile_progressive_web_app_features()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('standalone', false)
            ->assertSee('display', false)
            ->assertSee('start_url', false);
    }
}
