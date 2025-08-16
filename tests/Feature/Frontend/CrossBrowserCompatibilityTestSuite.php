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
use PHPUnit\Framework\Attributes\Test;

class CrossBrowserCompatibilityTestSuite extends TestCase
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

    #[Test]
    public function chrome_user_agent_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ])->get('/');

        $response->assertOk()
            ->assertSee('webkit', false) // WebKit vendor prefixes
            ->assertSee('chrome', false);
    }

    #[Test]
    public function firefox_user_agent_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0'
        ])->get('/');

        $response->assertOk()
            ->assertSee('moz', false) // Mozilla vendor prefixes
            ->assertSee('firefox', false);
    }

    #[Test]
    public function safari_user_agent_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('webkit', false) // WebKit vendor prefixes
            ->assertSee('safari', false);
    }

    #[Test]
    public function edge_user_agent_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
        ])->get('/');

        $response->assertOk()
            ->assertSee('webkit', false) // WebKit vendor prefixes
            ->assertSee('edge', false);
    }

    #[Test]
    public function mobile_chrome_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 10; SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
        ])->get('/');

        $response->assertOk()
            ->assertSee('mobile', false)
            ->assertSee('responsive', false)
            ->assertSee('touch', false);
    }

    #[Test]
    public function mobile_safari_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        ])->get('/');

        $response->assertOk()
            ->assertSee('ios', false)
            ->assertSee('mobile', false)
            ->assertSee('webkit', false);
    }

    #[Test]
    public function css_grid_fallback_support()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('display: grid', false)
            ->assertSee('display: flex', false) // Fallback
            ->assertSee('@supports (display: grid)', false);
    }

    #[Test]
    public function flexbox_fallback_support()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('display: flex', false)
            ->assertSee('display: block', false) // Fallback
            ->assertSee('@supports (display: flex)', false);
    }

    #[Test]
    public function vendor_prefixes_are_present()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('-webkit-', false)
            ->assertSee('-moz-', false)
            ->assertSee('-ms-', false)
            ->assertSee('-o-', false);
    }

    #[Test]
    public function progressive_enhancement_works()
    {
        $response = $this->get('/products');

        // Should work without JavaScript
        $response->assertOk()
            ->assertSee('<form', false) // Basic form functionality
            ->assertSee('method="GET"', false) // Server-side filtering
            ->assertSee('<noscript>', false); // No-JS fallback content
    }

    #[Test]
    public function css_feature_detection()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('@supports', false)
            ->assertSee('modernizr', false)
            ->assertSee('no-js', false);
    }

    #[Test]
    public function polyfill_support()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('polyfill', false)
            ->assertSee('fetch', false)
            ->assertSee('Promise', false);
    }

    #[Test]
    public function internet_explorer_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko'
        ])->get('/');

        $response->assertOk()
            ->assertSee('ie-warning', false) // IE compatibility notice
            ->assertSee('upgrade-browser', false);
    }

    #[Test]
    public function performance_optimizations_for_different_browsers()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('preload', false)      // Resource preloading
            ->assertSee('prefetch', false)     // Resource prefetching
            ->assertSee('dns-prefetch', false) // DNS prefetching
            ->assertSee('preconnect', false);  // Connection preloading
    }

    #[Test]
    public function keyboard_navigation_compatibility()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('tabindex', false)
            ->assertSee('aria-', false)
            ->assertSee('role=', false)
            ->assertSee('accesskey', false);
    }

    #[Test]
    public function touch_device_compatibility()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
        ])->get('/');

        $response->assertOk()
            ->assertSee('touch-action', false)
            ->assertSee('pointer-events', false)
            ->assertSee('user-select', false);
    }

    #[Test]
    public function print_media_compatibility()
    {
        $response = $this->get('/orders/1');

        $response->assertOk()
            ->assertSee('@media print', false)
            ->assertSee('print-only', false)
            ->assertSee('no-print', false);
    }

    #[Test]
    public function screen_reader_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('sr-only', false)
            ->assertSee('aria-label', false)
            ->assertSee('aria-describedby', false)
            ->assertSee('aria-expanded', false);
    }

    #[Test]
    public function color_scheme_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('prefers-color-scheme', false)
            ->assertSee('color-scheme', false)
            ->assertSee('dark', false)
            ->assertSee('light', false);
    }

    #[Test]
    public function reduced_motion_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('prefers-reduced-motion', false)
            ->assertSee('motion-safe', false)
            ->assertSee('motion-reduce', false);
    }

    #[Test]
    public function high_contrast_mode_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('prefers-contrast', false)
            ->assertSee('high-contrast', false)
            ->assertSee('forced-colors', false);
    }

    #[Test]
    public function font_display_optimization()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('font-display', false)
            ->assertSee('swap', false)
            ->assertSee('fallback', false);
    }

    #[Test]
    public function webp_image_fallback()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<picture>', false)
            ->assertSee('image/webp', false)
            ->assertSee('image/jpeg', false);
    }

    #[Test]
    public function service_worker_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('serviceWorker', false)
            ->assertSee('navigator.serviceWorker', false)
            ->assertSee('sw.js', false);
    }

    #[Test]
    public function intersection_observer_fallback()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('IntersectionObserver', false)
            ->assertSee('polyfill', false)
            ->assertSee('fallback', false);
    }

    #[Test]
    public function css_custom_properties_fallback()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('--', false) // CSS custom properties
            ->assertSee('var(', false)
            ->assertSee('fallback', false);
    }

    #[Test]
    public function es6_module_fallback()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('type="module"', false)
            ->assertSee('nomodule', false)
            ->assertSee('defer', false);
    }

    #[Test]
    public function responsive_images_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('srcset', false)
            ->assertSee('sizes', false)
            ->assertSee('loading="lazy"', false);
    }

    #[Test]
    public function form_validation_compatibility()
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('required', false)
            ->assertSee('pattern', false)
            ->assertSee('novalidate', false) // Custom validation fallback
            ->assertSee('data-validation', false);
    }

    #[Test]
    public function websocket_fallback_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('WebSocket', false)
            ->assertSee('EventSource', false) // SSE fallback
            ->assertSee('polling', false);    // Polling fallback
    }

    #[Test]
    public function local_storage_fallback()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('localStorage', false)
            ->assertSee('sessionStorage', false)
            ->assertSee('cookie', false); // Fallback
    }

    #[Test]
    public function geolocation_api_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('navigator.geolocation', false)
            ->assertSee('getCurrentPosition', false)
            ->assertSee('geo-fallback', false);
    }

    #[Test]
    public function payment_api_compatibility()
    {
        $response = $this->get('/checkout');

        $response->assertOk()
            ->assertSee('PaymentRequest', false)
            ->assertSee('canMakePayment', false)
            ->assertSee('traditional-checkout', false); // Fallback
    }

    #[Test]
    public function clipboard_api_compatibility()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('navigator.clipboard', false)
            ->assertSee('writeText', false)
            ->assertSee('execCommand', false); // Fallback
    }

    #[Test]
    public function notification_api_compatibility()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Notification', false)
            ->assertSee('requestPermission', false)
            ->assertSee('toast-fallback', false);
    }
}
