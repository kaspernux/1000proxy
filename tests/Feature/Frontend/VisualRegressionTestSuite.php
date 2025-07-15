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
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class VisualRegressionTestSuite extends DuskTestCase
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
    public function homepage_visual_regression_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('.navbar')
                ->screenshot('homepage-desktop');

            // Test mobile view
            $browser->resize(375, 667)
                ->refresh()
                ->waitFor('.navbar')
                ->screenshot('homepage-mobile');

            // Test tablet view
            $browser->resize(768, 1024)
                ->refresh()
                ->waitFor('.navbar')
                ->screenshot('homepage-tablet');
        });
    }

    /** @test */
    public function theme_switching_visual_test()
    {
        $this->browse(function (Browser $browser) {
            // Light theme
            $browser->visit('/')
                ->waitFor('.navbar')
                ->screenshot('theme-light');

            // Switch to dark theme
            $browser->click('[data-theme-toggle]')
                ->pause(500) // Wait for theme transition
                ->screenshot('theme-dark');

            // Test high contrast mode
            $browser->script('document.documentElement.classList.add("high-contrast")');
            $browser->pause(500)
                ->screenshot('theme-high-contrast');
        });
    }

    /** @test */
    public function product_listing_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products')
                ->waitFor('.product-card')
                ->screenshot('products-listing-desktop');

            // Test with filters applied
            $browser->select('[name="location"]', 'US')
                ->select('[name="category"]', $this->category->id)
                ->waitFor('.filter-results')
                ->screenshot('products-filtered');

            // Test empty state
            $browser->select('[name="location"]', 'XX') // Non-existent location
                ->waitFor('.empty-state')
                ->screenshot('products-empty-state');

            // Mobile view
            $browser->resize(375, 667)
                ->visit('/products')
                ->waitFor('.product-card')
                ->screenshot('products-listing-mobile');
        });
    }

    /** @test */
    public function cart_and_checkout_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customer, 'customer')
                ->visit('/products')
                ->waitFor('.product-card')
                ->click('.add-to-cart-btn')
                ->waitFor('.cart-notification')
                ->screenshot('cart-notification');

            // Cart page
            $browser->visit('/cart')
                ->waitFor('.cart-item')
                ->screenshot('cart-page');

            // Checkout process
            $browser->click('.checkout-btn')
                ->waitFor('.checkout-form')
                ->screenshot('checkout-step-1');

            // Payment step
            $browser->type('email', 'test@example.com')
                ->click('.next-step')
                ->waitFor('.payment-methods')
                ->screenshot('checkout-payment');
        });
    }

    /** @test */
    public function admin_panel_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->waitFor('.fi-sidebar')
                ->screenshot('admin-dashboard');

            // Customers management
            $browser->visit('/admin/customer-management/customers')
                ->waitFor('.fi-table')
                ->screenshot('admin-customers');

            // Server management
            $browser->visit('/admin/server-management/servers')
                ->waitFor('.fi-table')
                ->screenshot('admin-servers');

            // Mobile admin view
            $browser->resize(375, 667)
                ->visit('/admin')
                ->waitFor('.fi-sidebar')
                ->screenshot('admin-mobile');
        });
    }

    /** @test */
    public function customer_panel_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customer, 'customer')
                ->visit('/customer')
                ->waitFor('.fi-sidebar')
                ->screenshot('customer-dashboard');

            // My services
            $browser->visit('/customer/my-services')
                ->waitFor('.fi-table')
                ->screenshot('customer-services');

            // My orders
            $browser->visit('/customer/my-orders')
                ->waitFor('.fi-table')
                ->screenshot('customer-orders');

            // Wallet
            $browser->visit('/customer/my-wallet')
                ->waitFor('.wallet-balance')
                ->screenshot('customer-wallet');
        });
    }

    /** @test */
    public function form_validation_visual_test()
    {
        $this->browse(function (Browser $browser) {
            // Registration form errors
            $browser->visit('/register')
                ->press('Register')
                ->waitFor('.error-message')
                ->screenshot('form-validation-errors');

            // Login form with invalid credentials
            $browser->visit('/login')
                ->type('email', 'invalid@email.com')
                ->type('password', 'wrongpassword')
                ->press('Login')
                ->waitFor('.alert-danger')
                ->screenshot('login-error');

            // Successful form submission
            $browser->type('email', $this->customer->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitFor('.dashboard')
                ->screenshot('login-success');
        });
    }

    /** @test */
    public function loading_states_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products')
                ->waitFor('.product-card');

            // Simulate slow loading with JavaScript
            $browser->script('
                document.querySelector(".filter-form").style.opacity = "0.5";
                document.querySelector(".loading-spinner").style.display = "block";
            ');

            $browser->screenshot('loading-state');

            // Test skeleton loaders
            $browser->script('
                document.querySelectorAll(".product-card").forEach(card => {
                    card.innerHTML = `
                        <div class="skeleton-loader">
                            <div class="skeleton-image"></div>
                            <div class="skeleton-text"></div>
                            <div class="skeleton-text"></div>
                        </div>
                    `;
                });
            ');

            $browser->screenshot('skeleton-loading');
        });
    }

    /** @test */
    public function responsive_breakpoints_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products');

            // Extra small (mobile)
            $browser->resize(320, 568)
                ->waitFor('.product-card')
                ->screenshot('responsive-xs');

            // Small (mobile landscape)
            $browser->resize(576, 320)
                ->waitFor('.product-card')
                ->screenshot('responsive-sm');

            // Medium (tablet)
            $browser->resize(768, 1024)
                ->waitFor('.product-card')
                ->screenshot('responsive-md');

            // Large (desktop)
            $browser->resize(992, 768)
                ->waitFor('.product-card')
                ->screenshot('responsive-lg');

            // Extra large (wide desktop)
            $browser->resize(1200, 900)
                ->waitFor('.product-card')
                ->screenshot('responsive-xl');

            // Ultra wide
            $browser->resize(1920, 1080)
                ->waitFor('.product-card')
                ->screenshot('responsive-xxl');
        });
    }

    /** @test */
    public function modal_and_popup_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products')
                ->waitFor('.product-card')
                ->click('.product-details-btn')
                ->waitFor('.modal')
                ->screenshot('product-details-modal');

            // Close modal and test confirmation dialog
            $browser->click('.close-modal')
                ->click('.delete-btn')
                ->waitFor('.confirmation-dialog')
                ->screenshot('confirmation-dialog');

            // Test toast notifications
            $browser->script('
                window.showToast("Success!", "Operation completed successfully", "success");
            ');
            $browser->waitFor('.toast')
                ->screenshot('toast-notification');
        });
    }

    /** @test */
    public function accessibility_visual_indicators_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products');

            // Test focus indicators
            $browser->script('document.querySelector(".btn-primary").focus();');
            $browser->screenshot('focus-indicators');

            // Test high contrast mode
            $browser->script('document.documentElement.classList.add("high-contrast");');
            $browser->screenshot('high-contrast-mode');

            // Test large text mode
            $browser->script('document.documentElement.style.fontSize = "120%";');
            $browser->screenshot('large-text-mode');

            // Test keyboard navigation indicators
            $browser->keys('body', '{tab}', '{tab}', '{tab}');
            $browser->screenshot('keyboard-navigation');
        });
    }

    /** @test */
    public function error_pages_visual_test()
    {
        $this->browse(function (Browser $browser) {
            // 404 error page
            $browser->visit('/non-existent-page')
                ->waitFor('.error-page')
                ->screenshot('error-404');

            // 500 error simulation
            $browser->visit('/products')
                ->script('
                    document.body.innerHTML = `
                        <div class="error-page">
                            <h1>500 - Server Error</h1>
                            <p>Something went wrong on our end.</p>
                        </div>
                    `;
                ');
            $browser->screenshot('error-500');

            // Network error simulation
            $browser->script('
                document.body.innerHTML = `
                    <div class="error-page offline">
                        <h1>No Internet Connection</h1>
                        <p>Please check your connection and try again.</p>
                    </div>
                `;
            ');
            $browser->screenshot('error-offline');
        });
    }

    /** @test */
    public function animation_and_transition_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products');

            // Test hover animations
            $browser->script('
                document.querySelector(".product-card").classList.add("hover");
            ');
            $browser->screenshot('card-hover-animation');

            // Test slide transitions
            $browser->click('.filter-toggle')
                ->pause(300) // Wait for slide animation
                ->screenshot('filter-slide-animation');

            // Test fade transitions
            $browser->script('
                document.querySelector(".product-grid").style.opacity = "0.5";
                document.querySelector(".product-grid").style.transition = "opacity 0.3s ease";
            ');
            $browser->screenshot('fade-transition');

            // Test loading animations
            $browser->script('
                document.querySelector(".loading-spinner").style.display = "block";
                document.querySelector(".loading-spinner").classList.add("spin");
            ');
            $browser->screenshot('loading-animation');
        });
    }

    /** @test */
    public function print_layout_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customer, 'customer')
                ->visit('/orders/1'); // Assuming order exists

            // Simulate print preview
            $browser->script('
                document.body.classList.add("print-preview");
                // Hide non-printable elements
                document.querySelectorAll(".no-print").forEach(el => el.style.display = "none");
                // Show print-only elements
                document.querySelectorAll(".print-only").forEach(el => el.style.display = "block");
            ');

            $browser->screenshot('print-layout');
        });
    }

    /** @test */
    public function cross_browser_compatibility_visual_test()
    {
        // This test would typically be run with different WebDriver configurations
        // for Chrome, Firefox, Safari, Edge, etc.

        $this->browse(function (Browser $browser) {
            $browser->visit('/products')
                ->waitFor('.product-card');

            // Test CSS Grid fallbacks
            $browser->script('
                // Simulate older browser without CSS Grid support
                CSS.supports = function() { return false; };
                document.documentElement.classList.add("no-css-grid");
            ');
            $browser->screenshot('no-css-grid-fallback');

            // Test Flexbox fallbacks
            $browser->script('
                document.documentElement.classList.add("no-flexbox");
            ');
            $browser->screenshot('no-flexbox-fallback');

            // Test without JavaScript
            $browser->script('
                document.querySelectorAll("script").forEach(script => script.remove());
            ');
            $browser->refresh()
                ->waitFor('body')
                ->screenshot('no-javascript-fallback');
        });
    }

    /** @test */
    public function data_visualization_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->waitFor('.chart-container');

            // Test different chart types
            $browser->script('
                // Simulate chart rendering
                document.querySelector(".revenue-chart").innerHTML = `
                    <canvas width="400" height="200" style="background: linear-gradient(45deg, #blue, #green);"></canvas>
                `;
            ');
            $browser->screenshot('revenue-chart');

            $browser->script('
                document.querySelector(".user-activity-chart").innerHTML = `
                    <canvas width="400" height="200" style="background: linear-gradient(45deg, #red, #orange);"></canvas>
                `;
            ');
            $browser->screenshot('user-activity-chart');

            // Test chart responsiveness
            $browser->resize(375, 667);
            $browser->screenshot('charts-mobile');
        });
    }

    /** @test */
    public function component_library_showcase_visual_test()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/components/showcase')
                ->waitFor('.component-showcase');

            // Test all button variants
            $browser->screenshot('button-variants');

            // Test form components
            $browser->click('[data-tab="forms"]')
                ->waitFor('.form-components')
                ->screenshot('form-components');

            // Test card components
            $browser->click('[data-tab="cards"]')
                ->waitFor('.card-components')
                ->screenshot('card-components');

            // Test navigation components
            $browser->click('[data-tab="navigation"]')
                ->waitFor('.navigation-components')
                ->screenshot('navigation-components');
        });
    }
}
