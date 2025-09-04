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
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ServerCategory $category;
    protected ServerBrand $brand;
    protected Server $server;
    protected ServerPlan $serverPlan;
    protected $customers;
    protected $orders;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'name' => 'Admin User',
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

        // Create customers
        $this->customers = Customer::factory(5)->create();

        // Create orders
        $this->orders = Order::factory(3)->create([
            'customer_id' => $this->customers->first()->id,
        ]);
    }

    #[Test]
    public function admin_can_access_admin_panel()
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    #[Test]
    public function admin_can_view_customers_resource()
    {
        $this->actingAs($this->admin)
            ->get('/admin/customer-management/customers')
            ->assertOk()
            ->assertSee('Customers');
    }

    #[Test]
    public function admin_can_view_orders_resource()
    {
        $this->actingAs($this->admin)
            ->get('/admin/order-management/orders')
            ->assertOk()
            ->assertSee('Orders');
    }

    #[Test]
    public function admin_can_view_servers_resource()
    {
        $this->actingAs($this->admin)
            ->get('/admin/server-management/servers')
            ->assertOk()
            ->assertSee('Servers');
    }

    #[Test]
    public function admin_can_view_server_plans_resource()
    {
        $this->actingAs($this->admin)
            ->get('/admin/server-management/server-plans')
            ->assertOk()
            ->assertSee('Server Plans');
    }

    #[Test]
    public function admin_can_create_customer()
    {
        $this->actingAs($this->admin);

        $customerData = [
            'name' => 'New Customer',
            'email' => 'new@customer.com',
            'password' => 'password123',
            'is_active' => true,
        ];

        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\CreateCustomer::class)
            ->fillForm($customerData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'New Customer',
            'email' => 'new@customer.com',
        ]);
    }

    #[Test]
    public function admin_can_edit_customer()
    {
        $this->actingAs($this->admin);

        $customer = $this->customers->first();

        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\EditCustomer::class, [
                'record' => $customer->id,
            ])
            ->fillForm([
                'name' => 'Updated Customer Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer Name',
        ]);
    }

    #[Test]
    public function admin_can_delete_customer()
    {
        $this->actingAs($this->admin);

        $customer = $this->customers->first();

        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\ListCustomers::class)
            ->callTableAction('delete', $customer->id);

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    #[Test]
    public function admin_can_create_server()
    {
        $this->actingAs($this->admin);

        $serverData = [
            'name' => 'New Test Server',
            'location' => 'UK',
            'ip_address' => '192.168.1.200',
            'panel_port' => '2054',
            'panel_username' => 'admin',
            'panel_password' => 'password',
            'is_active' => true,
        ];

        Livewire::test(\App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages\CreateServer::class)
            ->fillForm($serverData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'New Test Server',
            'location' => 'UK',
        ]);
    }

    #[Test]
    public function admin_can_create_server_plan()
    {
        $this->actingAs($this->admin);

        $planData = [
            'server_id' => $this->server->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'New Gaming Plan',
            'price' => 14.99,
            'is_active' => true,
        ];

        Livewire::test(\App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages\CreateServerPlan::class)
            ->fillForm($planData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('server_plans', [
            'name' => 'New Gaming Plan',
            'price' => 14.99,
        ]);
    }

    #[Test]
    public function admin_can_filter_customers()
    {
        $this->actingAs($this->admin);

        // Test filtering by active status
        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\ListCustomers::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords($this->customers->where('is_active', true));
    }

    #[Test]
    public function admin_can_search_customers()
    {
        $this->actingAs($this->admin);

        $customer = $this->customers->first();

        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\ListCustomers::class)
            ->searchTable($customer->name)
            ->assertCanSeeTableRecords([$customer]);
    }

    #[Test]
    public function admin_can_bulk_delete_customers()
    {
        $this->actingAs($this->admin);

        $customers = $this->customers->take(2);

        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\ListCustomers::class)
            ->callTableBulkAction('delete', $customers->pluck('id')->toArray());

        foreach ($customers as $customer) {
            $this->assertDatabaseMissing('customers', [
                'id' => $customer->id,
            ]);
        }
    }

    #[Test]
    public function admin_panel_form_validation_works()
    {
        $this->actingAs($this->admin);

        // Test customer creation with invalid email
        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\CreateCustomer::class)
            ->fillForm([
                'name' => 'Test Customer',
                'email' => 'invalid-email',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);

        // Test server creation with invalid IP
        Livewire::test(\App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages\CreateServer::class)
            ->fillForm([
                'name' => 'Test Server',
                'ip_address' => 'invalid-ip',
                'panel_port' => '2053',
            ])
            ->call('create')
            ->assertHasFormErrors(['ip_address']);
    }

    #[Test]
    public function admin_panel_relationships_work()
    {
        $this->actingAs($this->admin);

        // Test that server plan shows related server
        Livewire::test(\App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages\ViewServerPlan::class, [
                'record' => $this->serverPlan->id,
            ])
            ->assertSee($this->server->name);

        // Test that customer resource shows customer data
        Livewire::test(\App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages\ViewCustomer::class, [
                'record' => $this->customers->first()->id,
            ])
            ->assertSee($this->customers->first()->name);
    }

    #[Test]
    public function admin_panel_is_mobile_responsive()
    {
        $this->actingAs($this->admin);

        // Test mobile viewport
        $response = $this->get('/admin/customer-management/customers');

        $response->assertSee('class="responsive"')
                ->assertSee('viewport')
                ->assertSee('mobile');
    }
}
