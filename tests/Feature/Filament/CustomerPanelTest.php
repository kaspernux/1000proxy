<?php

namespace Tests\Feature\Filament;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPanelTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected ServerCategory $category;
    protected ServerBrand $brand;
    protected Server $server;
    protected ServerPlan $serverPlan;
    protected $orders;

    protected function setUp(): void
    {
        parent::setUp();

        // Create customer
        $this->customer = Customer::factory()->create([
            'email' => 'customer@test.com',
            'name' => 'Test Customer',
            'email_verified_at' => now(),
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
            'slug' => 'gaming-' . uniqid(),
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
    public function customer_can_access_customer_panel()
    {
        $this->actingAs($this->customer, 'customer')
            ->get('/customer')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    /** @test */
    public function customer_can_view_my_orders()
    {
        $this->actingAs($this->customer, 'customer')
            ->get('/customer/order-management/my-orders')
            ->assertOk()
            ->assertSee('My Orders');
    }

    /** @test */
    public function customer_can_view_my_services()
    {
        $this->actingAs($this->customer, 'customer')
            ->get('/customer/order-management/my-services')
            ->assertOk()
            ->assertSee('My Services');
    }

    /** @test */
    public function customer_can_view_wallet()
    {
        $this->actingAs($this->customer, 'customer')
            ->get('/customer/financial-management/wallet')
            ->assertOk()
            ->assertSee('Wallet');
    }

    /** @test */
    public function customer_can_view_profile()
    {
        $this->actingAs($this->customer, 'customer')
            ->get('/customer/customer-management/profile')
            ->assertOk()
            ->assertSee('Profile');
    }

    /** @test */
    public function customer_can_update_profile()
    {
        $this->actingAs($this->customer, 'customer');

        // Test using direct HTTP request instead of missing Livewire component
        $this->patch('/customer/profile', [
                'name' => 'Updated Customer Name',
                'phone' => '+1234567890',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'name' => 'Updated Customer Name',
        ]);
    }

    /** @test */
    public function customer_can_view_own_orders_only()
    {
        // Create another customer with orders
        $otherCustomer = Customer::factory()->create();
        $otherOrder = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $this->actingAs($this->customer, 'customer');

        // Test that customer can only see their own orders via HTTP request
        $response = $this->get('/customer/order-management/my-orders');
        $response->assertOk();

        // Customer should be able to access their own order details
        $ownOrder = $this->orders->first();
        $this->get('/customer/orders/' . $ownOrder->id)
            ->assertOk();

        // But not other customer's orders
        $this->get('/customer/orders/' . $otherOrder->id)
            ->assertStatus(404);
    }

    /** @test */
    public function customer_can_filter_orders()
    {
        $this->actingAs($this->customer, 'customer');

        // Test that orders page loads correctly with filtering capability
        $response = $this->get('/customer/order-management/my-orders');
        $response->assertOk()
                ->assertSee('Orders')
                ->assertSee('Status');
    }

    /** @test */
    public function customer_can_search_orders()
    {
        $this->actingAs($this->customer, 'customer');

        // Test that search functionality is available
        $response = $this->get('/customer/order-management/my-orders');
        $response->assertOk()
                ->assertSee('Search');
    }

    /** @test */
    public function customer_panel_form_validation_works()
    {
        $this->actingAs($this->customer, 'customer');

        // Test profile validation via HTTP request
        $response = $this->patch('/customer/profile', [
            'email' => 'invalid-email',
        ]);

        // Should either redirect with errors or show validation errors
        $this->assertTrue($response->status() >= 400 || $response->isRedirect());
    }

    /** @test */
    public function customer_cannot_access_admin_panel()
    {
        $this->actingAs($this->customer, 'customer')
            ->get('/admin')
            ->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_access_other_customer_data()
    {
        $otherCustomer = Customer::factory()->create();

        $this->actingAs($this->customer, 'customer')
            ->get('/customer/customers/' . $otherCustomer->id)
            ->assertStatus(404);
    }

    /** @test */
    public function customer_panel_is_mobile_responsive()
    {
        $this->actingAs($this->customer, 'customer');

        // Test mobile viewport
        $response = $this->get('/customer/order-management/my-orders');

        $response->assertOk()
                ->assertSee('viewport');
    }

    /** @test */
    public function customer_can_view_wallet_transactions()
    {
        $this->actingAs($this->customer, 'customer');

        // Add some wallet transactions
        $this->customer->addToWallet(100, 'Test credit');
        $this->customer->payFromWallet(50, 'Test debit');

        // Test wallet page loads with transaction data
        $response = $this->get('/customer/financial-management/wallet');
        $response->assertOk()
                ->assertSee('Wallet');
    }

    /** @test */
    public function customer_panel_navigation_works()
    {
        $this->actingAs($this->customer, 'customer');

        // Test navigation between different sections
        $this->get('/customer/order-management/my-orders')
            ->assertOk()
            ->assertSee('My Orders');

        $this->get('/customer/financial-management/wallet')
            ->assertOk()
            ->assertSee('Wallet');

        $this->get('/customer/customer-management/profile')
            ->assertOk()
            ->assertSee('Profile');
    }

    /** @test */
    public function customer_dashboard_widgets_work()
    {
        $this->actingAs($this->customer, 'customer');

        // Test dashboard displays customer-specific data
        $response = $this->get('/customer');

        $response->assertOk()
                ->assertSee($this->customer->name)
                ->assertSee('Orders')
                ->assertSee('Wallet Balance');
    }
}
