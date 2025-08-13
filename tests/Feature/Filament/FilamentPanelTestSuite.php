<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Server;
use App\Models\Service;
use App\Models\Order;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class FilamentPanelTestSuite extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_access_admin_panel()
    {
        $this->actingAs($this->adminUser)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    /** @test */
    public function customer_cannot_access_admin_panel()
    {
        $this->actingAs($this->customerUser)
            ->get('/admin')
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_view_users_resource()
    {
        User::factory()->count(5)->create();

        $this->actingAs($this->adminUser)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Users');
    }

    /** @test */
    public function admin_can_create_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ];

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'customer',
        ]);
    }

    /** @test */
    public function admin_can_edit_user()
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\EditUser::class, [
                'record' => $user->getRouteKey(),
            ])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => $user->email,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableAction(DeleteAction::class, $user);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_can_view_servers_resource()
    {
        Server::factory()->count(3)->create();

        $this->actingAs($this->adminUser)
            ->get('/admin/servers')
            ->assertOk()
            ->assertSee('Servers');
    }

    /** @test */
    public function admin_can_create_server()
    {
        $serverData = [
            'name' => 'Test Server',
            'host' => '192.168.1.100',
            'port' => 2053,
            'username' => 'admin',
            'password' => 'password123',
            'status' => 'up',
            'location' => 'US',
            'provider' => 'DigitalOcean',
        ];

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\ServerResource\Pages\CreateServer::class)
            ->fillForm($serverData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'host' => '192.168.1.100',
            'port' => 2053,
        ]);
    }

    /** @test */
    public function admin_can_view_services_resource()
    {
        Service::factory()->count(3)->create();

        $this->actingAs($this->adminUser)
            ->get('/admin/services')
            ->assertOk()
            ->assertSee('Services');
    }

    /** @test */
    public function admin_can_create_service()
    {
        $serviceData = [
            'name' => 'Premium Proxy',
            'description' => 'High-speed premium proxy service',
            'price' => 19.99,
            'billing_cycle' => 'monthly',
            'status' => 'up',
            'features' => ['unlimited_bandwidth', 'multiple_locations'],
        ];

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\ServiceResource\Pages\CreateService::class)
            ->fillForm($serviceData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'name' => 'Premium Proxy',
            'price' => 19.99,
        ]);
    }

    /** @test */
    public function admin_can_view_orders_resource()
    {
        Order::factory()->count(5)->create();

        $this->actingAs($this->adminUser)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Orders');
    }

    /** @test */
    public function admin_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\OrderResource\Pages\EditOrder::class, [
                'record' => $order->getRouteKey(),
            ])
            ->fillForm([
                'status' => 'completed',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function customer_can_access_customer_panel()
    {
        $this->actingAs($this->customerUser)
            ->get('/customer')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    /** @test */
    public function customer_can_view_their_orders()
    {
        $order = Order::factory()->create(['user_id' => $this->customerUser->id]);
        $otherOrder = Order::factory()->create(); // Different user

        $this->actingAs($this->customerUser)
            ->get('/customer/orders')
            ->assertOk()
            ->assertSee($order->id)
            ->assertDontSee($otherOrder->id);
    }

    /** @test */
    public function customer_can_view_their_services()
    {
        $service = Service::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $this->customerUser->id,
            'service_id' => $service->id,
            'status' => 'up',
        ]);

        $this->actingAs($this->customerUser)
            ->get('/customer/services')
            ->assertOk()
            ->assertSee($service->name);
    }

    /** @test */
    public function filament_forms_validate_required_fields()
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => '',
                'password' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function filament_forms_validate_email_format()
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    /** @test */
    public function filament_forms_validate_unique_email()
    {
        $existingUser = User::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => $existingUser->email,
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    /** @test */
    public function filament_tables_can_filter_data()
    {
    $activeUser = User::factory()->create(['status' => 'active']); // user status unaffected
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->filterTable('status', 'active')
            ->assertCanSeeTableRecords([$activeUser])
            ->assertCanNotSeeTableRecords([$inactiveUser]);
    }

    /** @test */
    public function filament_tables_can_search_data()
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2]);
    }

    /** @test */
    public function filament_tables_can_sort_data()
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $user3 = User::factory()->create(['name' => 'Charlie']);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$user1, $user2, $user3], inOrder: true);
    }

    /** @test */
    public function filament_bulk_actions_work()
    {
        $users = User::factory()->count(3)->create();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableBulkAction(DeleteAction::class, $users);

        foreach ($users as $user) {
            $this->assertSoftDeleted('users', ['id' => $user->id]);
        }
    }

    /** @test */
    public function filament_widgets_display_correct_data()
    {
        // Create test data
        User::factory()->count(10)->create();
        Order::factory()->count(15)->create();
        Server::factory()->count(5)->create();

        $this->actingAs($this->adminUser)
            ->get('/admin')
            ->assertOk()
            ->assertSee('10') // Users count
            ->assertSee('15') // Orders count
            ->assertSee('5'); // Servers count
    }

    /** @test */
    public function filament_notifications_work()
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'customer',
            ])
            ->call('create')
            ->assertNotified('User created successfully.');
    }

    /** @test */
    public function filament_pages_load_without_errors()
    {
        $pages = [
            '/admin',
            '/admin/users',
            '/admin/servers',
            '/admin/services',
            '/admin/orders',
            '/admin/settings',
        ];

        foreach ($pages as $page) {
            $this->actingAs($this->adminUser)
                ->get($page)
                ->assertOk();
        }
    }

    /** @test */
    public function filament_resource_permissions_work()
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        // Moderator should have limited access
        $this->actingAs($moderator)
            ->get('/admin/users')
            ->assertOk(); // Can view

        // But cannot delete critical resources
        $user = User::factory()->create();

        Livewire::actingAs($moderator)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->assertTableActionHidden(DeleteAction::class, $user);
    }

    /** @test */
    public function filament_export_functionality_works()
    {
        User::factory()->count(5)->create();

        $response = Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableAction('export');

        $this->assertNotNull($response);
    }

    /** @test */
    public function filament_import_functionality_works()
    {
        $csvContent = "name,email,role\nTest User,test@example.com,customer";
        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            stream_get_meta_data($tempFile)['uri'],
            'users.csv',
            'text/csv',
            null,
            true
        );

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
            ->callTableAction('import', data: ['file' => $uploadedFile]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function filament_global_search_works()
    {
        $user = User::factory()->create(['name' => 'Searchable User']);
        $server = Server::factory()->create(['name' => 'Searchable Server']);

        $this->actingAs($this->adminUser)
            ->get('/admin/search?query=Searchable')
            ->assertOk()
            ->assertSee('Searchable User')
            ->assertSee('Searchable Server');
    }

    /** @test */
    public function filament_dark_mode_toggle_works()
    {
        $this->actingAs($this->adminUser)
            ->get('/admin')
            ->assertOk();

        // Test dark mode toggle functionality
        $this->actingAs($this->adminUser)
            ->post('/admin/theme/toggle')
            ->assertSessionHas('theme', 'dark');
    }

    /** @test */
    public function filament_multi_tenancy_works()
    {
        // If multi-tenancy is implemented
        $tenant1 = User::factory()->create(['tenant_id' => 1]);
        $tenant2 = User::factory()->create(['tenant_id' => 2]);

        $order1 = Order::factory()->create(['user_id' => $tenant1->id]);
        $order2 = Order::factory()->create(['user_id' => $tenant2->id]);

        // User from tenant 1 should only see their data
        Livewire::actingAs($tenant1)
            ->test(\App\Filament\Resources\OrderResource\Pages\ListOrders::class)
            ->assertCanSeeTableRecords([$order1])
            ->assertCanNotSeeTableRecords([$order2]);
    }

    /** @test */
    public function filament_custom_actions_work()
    {
        $server = Server::factory()->create(['status' => 'inactive']);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\ServerResource\Pages\ListServers::class)
            ->callTableAction('activate', $server);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function filament_relationship_management_works()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Filament\Resources\UserResource\Pages\EditUser::class, [
                'record' => $user->getRouteKey(),
            ])
            ->callRelationManagerAction('orders', 'create', [
                'service_id' => $service->id,
                'status' => 'pending',
                'amount' => 99.99,
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'amount' => 99.99,
        ]);
    }
}
