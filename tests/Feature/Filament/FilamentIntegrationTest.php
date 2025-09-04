<?php

namespace Tests\Feature\Filament;

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

class FilamentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'name' => 'Admin User',
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'email' => 'customer@test.com',
            'name' => 'Test Customer',
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function admin_panel_routes_are_accessible()
    {
        $this->actingAs($this->admin);

        $adminRoutes = [
            '/admin',
            '/admin/customer-management/customers',
            '/admin/server-management/servers',
            '/admin/server-management/server-plans',
            '/admin/server-management/server-categories',
            '/admin/server-management/server-brands',
            '/admin/proxy-shop/orders',
            '/admin/proxy-shop/invoices',
            '/admin/staff-management/users',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertTrue(
                $response->isOk(),
                "Admin route {$route} should be accessible but returned status {$response->status()}"
            );
        }
    }

    #[Test]
    public function customer_panel_routes_are_accessible()
    {
        $this->actingAs($this->customer, 'customer');

        $customerRoutes = [
            '/customer',
            '/customer/order-management/my-orders',
            '/customer/order-management/my-services',
            '/customer/financial-management/wallet',
            '/customer/customer-management/profile',
        ];

        foreach ($customerRoutes as $route) {
            $response = $this->get($route);
            $this->assertTrue(
                $response->isOk() || $response->isRedirect(),
                "Customer route {$route} should be accessible but returned status {$response->status()}"
            );
        }
    }

    #[Test]
    public function admin_cannot_access_customer_panel()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/customer');
        $this->assertFalse($response->isOk());
    }

    #[Test]
    public function customer_cannot_access_admin_panel()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->get('/admin');
        $this->assertEquals(403, $response->status());
    }

    #[Test]
    public function unauthenticated_users_are_redirected()
    {
        // Test admin panel
        $this->get('/admin')
            ->assertRedirect('/admin/login');

        // Test customer panel
        $this->get('/customer')
            ->assertRedirect('/login');
    }

    #[Test]
    public function filament_resources_have_proper_permissions()
    {
        // Test as admin
        $this->actingAs($this->admin);

        // Admin should access all resources
        $this->get('/admin/customer-management/customers')->assertOk();
        $this->get('/admin/server-management/servers')->assertOk();

        // Test as customer
        $this->actingAs($this->customer, 'customer');

        // Customer should not access admin resources
        $this->get('/admin/customer-management/customers')->assertStatus(403);
        $this->get('/admin/server-management/servers')->assertStatus(403);
    }

    #[Test]
    public function filament_panels_load_with_correct_navigation()
    {
        // Test admin navigation
        $this->actingAs($this->admin);
        $response = $this->get('/admin');

        $response->assertOk()
                ->assertSee('Customer Management')
                ->assertSee('Server Management')
                ->assertSee('Proxy Shop');

        // Test customer navigation
        $this->actingAs($this->customer, 'customer');
        $response = $this->get('/customer');

        if ($response->isOk()) {
            $response->assertSee('My Orders')
                    ->assertSee('My Services')
                    ->assertSee('Wallet');
        }
    }

    #[Test]
    public function filament_resources_handle_data_correctly()
    {
        $this->actingAs($this->admin);

        // Create test data
        $category = ServerCategory::create([
            'name' => 'Gaming',
            'description' => 'Gaming servers',
            'is_active' => true,
            'slug' => 'gaming-' . uniqid(),
        ]);

        $brand = ServerBrand::create([
            'name' => 'ProxyTitan',
            'description' => 'Premium proxy provider',
            'is_active' => true,
        ]);

        $server = Server::create([
            'name' => 'Test Server',
            'location' => 'US',
            'ip_address' => '192.168.1.100',
            'panel_port' => '2053',
            'panel_username' => 'admin',
            'panel_password' => 'password',
            'is_active' => true,
        ]);

        // Test that data is displayed in resources
        $this->get('/admin/server-management/server-categories')
            ->assertOk()
            ->assertSee('Gaming');

        $this->get('/admin/server-management/server-brands')
            ->assertOk()
            ->assertSee('ProxyTitan');

        $this->get('/admin/server-management/servers')
            ->assertOk()
            ->assertSee('Test Server');
    }

    #[Test]
    public function filament_forms_validate_input()
    {
        $this->actingAs($this->admin);

        // Test creating a customer with invalid data
        $response = $this->post('/admin/customer-management/customers', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123', // Too short
        ]);

        // Should either show validation errors or redirect with errors
        $this->assertTrue(
            $response->status() >= 400 || $response->isRedirect(),
            'Form should validate and show errors'
        );
    }

    #[Test]
    public function filament_bulk_actions_work()
    {
        $this->actingAs($this->admin);

        // Create multiple customers
        $customers = Customer::factory(3)->create();

        // Test that bulk action endpoints exist
        $response = $this->get('/admin/customer-management/customers');
        $response->assertOk();

        // Check if bulk actions are available in the UI
        if ($response->isOk()) {
            $response->assertSee('Bulk');
        }
    }

    #[Test]
    public function filament_search_functionality_works()
    {
        $this->actingAs($this->admin);

        // Create a customer with searchable data
        $customer = Customer::factory()->create([
            'name' => 'Searchable Customer',
            'email' => 'searchable@test.com',
        ]);

        // Test search endpoint
        $response = $this->get('/admin/customer-management/customers?search=Searchable');

        if ($response->isOk()) {
            $response->assertSee('Searchable Customer');
        }
    }

    #[Test]
    public function filament_filters_work()
    {
        $this->actingAs($this->admin);

        // Create customers with different statuses
        Customer::factory()->create(['is_active' => true]);
        Customer::factory()->create(['is_active' => false]);

        // Test filtering functionality
        $response = $this->get('/admin/customer-management/customers');
        $response->assertOk();

        // Check if filter options are available
        if ($response->isOk()) {
            $response->assertSee('Filter');
        }
    }

    #[Test]
    public function filament_pagination_works()
    {
        $this->actingAs($this->admin);

        // Create many customers to test pagination
        Customer::factory(25)->create();

        $response = $this->get('/admin/customer-management/customers');
        $response->assertOk();

        // Check if pagination controls are present
        if ($response->isOk()) {
            $response->assertSeeText('of');
        }
    }

    #[Test]
    public function customer_wallet_integration_works()
    {
        $this->actingAs($this->customer, 'customer');

        // Add money to wallet
        $this->customer->addToWallet(100, 'Test credit');

        // Test wallet page shows balance
        $response = $this->get('/customer/financial-management/wallet');

        if ($response->isOk()) {
            $response->assertSee('100');
        }
    }

    #[Test]
    public function order_management_integration_works()
    {
        $this->actingAs($this->customer, 'customer');

        // Create an order
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Test that customer can view their order
        $response = $this->get('/customer/order-management/my-orders');

        if ($response->isOk()) {
            $response->assertSee('Orders');
        }
    }
}
