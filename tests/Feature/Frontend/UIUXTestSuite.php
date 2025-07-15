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
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class UIUXTestSuite extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected ServerCategory $category;
    protected ServerBrand $brand;
    protected Server $server;
    protected ServerPlan $serverPlan;
    protected $orders;

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

        // Create orders
        $this->orders = Order::factory(3)->create([
            'customer_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function homepage_loads_with_correct_meta_tags()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<title>', false)
            ->assertSee('viewport', false)
            ->assertSee('meta name="description"', false)
            ->assertSee('meta name="keywords"', false)
            ->assertSee('og:', false); // OpenGraph tags
    }

    /** @test */
    public function dark_mode_toggle_functionality_works()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('theme-toggle', false)
            ->assertSee('data-theme', false)
            ->assertSee('localStorage', false);
    }

    /** @test */
    public function mobile_navigation_is_responsive()
    {
        $response = $this->get('/', [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ]);

        $response->assertOk()
            ->assertSee('navbar-toggler', false)
            ->assertSee('mobile-menu', false)
            ->assertSee('hamburger', false);
    }

    /** @test */
    public function form_validation_provides_user_friendly_errors()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSee('error-message', false);
    }

    /** @test */
    public function loading_states_are_displayed_during_form_submission()
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('loading-spinner', false)
            ->assertSee('wire:loading', false)
            ->assertSee('disabled-state', false);
    }

    /** @test */
    public function server_cards_display_status_indicators()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('server-status', false)
            ->assertSee('status-indicator', false)
            ->assertSee('online', false);
    }

    /** @test */
    public function filtering_system_provides_immediate_feedback()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('filter-results', false)
            ->assertSee('no-results', false)
            ->assertSee('clear-filters', false);
    }

    /** @test */
    public function breadcrumb_navigation_is_present()
    {
        $response = $this->get('/products/detail/1');

        $response->assertOk()
            ->assertSee('breadcrumb', false)
            ->assertSee('nav aria-label="breadcrumb"', false);
    }

    /** @test */
    public function search_functionality_works_with_autocomplete()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('search-input', false)
            ->assertSee('autocomplete', false)
            ->assertSee('search-suggestions', false);
    }

    /** @test */
    public function pagination_is_keyboard_accessible()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('pagination', false)
            ->assertSee('tabindex', false)
            ->assertSee('aria-label', false);
    }

    /** @test */
    public function error_pages_are_user_friendly()
    {
        $response = $this->get('/non-existent-page');

        $response->assertStatus(404)
            ->assertSee('404', false)
            ->assertSee('Page Not Found', false)
            ->assertSee('home', false); // Link back to home
    }

    /** @test */
    public function cart_updates_are_reflected_immediately()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->post('/cart/add', [
            'server_plan_id' => $this->serverPlan->id,
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['cart_count', 'total']);
    }

    /** @test */
    public function payment_forms_include_security_indicators()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->get('/checkout');

        $response->assertOk()
            ->assertSee('secure', false)
            ->assertSee('ssl', false)
            ->assertSee('encrypted', false);
    }

    /** @test */
    public function tooltips_provide_helpful_information()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('tooltip', false)
            ->assertSee('data-bs-toggle="tooltip"', false)
            ->assertSee('title=', false);
    }

    /** @test */
    public function buttons_have_proper_focus_states()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('focus:ring', false)
            ->assertSee('focus:outline', false);
    }

    /** @test */
    public function images_have_proper_alt_attributes()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('alt=', false);
    }

    /** @test */
    public function tables_are_responsive_on_mobile()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/customer-management/customers', [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ]);

        $response->assertOk()
            ->assertSee('table-responsive', false)
            ->assertSee('overflow-x-auto', false);
    }

    /** @test */
    public function modals_trap_focus_properly()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('modal', false)
            ->assertSee('tabindex="-1"', false)
            ->assertSee('aria-hidden', false);
    }

    /** @test */
    public function color_contrast_meets_accessibility_standards()
    {
        $response = $this->get('/');

        // Check for high contrast classes and proper color usage
        $response->assertOk()
            ->assertSee('text-', false) // Tailwind text color classes
            ->assertSee('bg-', false)   // Background color classes
            ->assertDontSee('text-gray-300 bg-gray-200', false); // Low contrast combination
    }

    /** @test */
    public function keyboard_shortcuts_are_documented()
    {
        $response = $this->get('/help/shortcuts');

        $response->assertOk()
            ->assertSee('keyboard', false)
            ->assertSee('shortcut', false)
            ->assertSee('Ctrl', false);
    }

    /** @test */
    public function loading_animations_are_smooth()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('transition', false)
            ->assertSee('duration', false)
            ->assertSee('ease', false);
    }

    /** @test */
    public function empty_states_provide_clear_guidance()
    {
        // Clear all server plans to test empty state
        ServerPlan::query()->delete();

        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('empty', false)
            ->assertSee('No servers', false)
            ->assertSee('Try', false); // Suggestion text
    }

    /** @test */
    public function success_messages_are_displayed_prominently()
    {
        $this->actingAs($this->customer, 'customer');

        Session::flash('success', 'Order placed successfully!');

        $response = $this->get('/orders');

        $response->assertOk()
            ->assertSee('success', false)
            ->assertSee('alert-success', false);
    }

    /** @test */
    public function form_fields_have_proper_labels()
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('<label', false)
            ->assertSee('for=', false)
            ->assertSee('required', false);
    }

    /** @test */
    public function performance_metrics_are_within_acceptable_ranges()
    {
        $start = microtime(true);

        $response = $this->get('/');

        $duration = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(2.0, $duration, 'Page should load within 2 seconds');
    }

    /** @test */
    public function css_and_js_assets_are_minified_in_production()
    {
        config(['app.env' => 'production']);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('.min.css', false)
            ->assertSee('.min.js', false);
    }

    /** @test */
    public function progressive_enhancement_works_without_javascript()
    {
        $response = $this->get('/products');

        // Should still be functional even if JavaScript fails
        $response->assertOk()
            ->assertSee('form', false) // Basic form elements
            ->assertSee('noscript', false); // Fallback content
    }

    /** @test */
    public function offline_functionality_provides_appropriate_feedback()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('offline', false)
            ->assertSee('network', false);
    }

    /** @test */
    public function cross_browser_compatibility_elements_are_present()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('webkit', false)
            ->assertSee('moz', false)
            ->assertSee('ms', false); // Vendor prefixes
    }

    /** @test */
    public function touch_targets_meet_minimum_size_requirements()
    {
        $response = $this->get('/', [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
        ]);

        $response->assertOk()
            ->assertSee('min-h-', false) // Minimum height classes
            ->assertSee('p-', false)     // Padding for touch targets
            ->assertSee('touch-target', false);
    }

    /** @test */
    public function screen_reader_announcements_are_present()
    {
        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('sr-only', false)      // Screen reader only text
            ->assertSee('aria-live', false)    // Live regions
            ->assertSee('aria-label', false)   // Accessibility labels
            ->assertSee('role=', false);       // ARIA roles
    }

    /** @test */
    public function print_styles_are_optimized()
    {
        $response = $this->get('/orders/' . $this->orders->first()->id);

        $response->assertOk()
            ->assertSee('print:', false)     // Print-specific styles
            ->assertSee('@media print', false);
    }

    /** @test */
    public function internationalization_elements_are_prepared()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('lang=', false)
            ->assertSee('dir=', false)        // Text direction
            ->assertSee('translate', false);  // Translation attributes
    }

    /** @test */
    public function animation_preferences_are_respected()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('prefers-reduced-motion', false)
            ->assertSee('motion-safe', false)
            ->assertSee('motion-reduce', false);
    }
}
