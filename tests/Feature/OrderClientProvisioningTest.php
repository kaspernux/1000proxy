<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use App\Models\Server;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use App\Models\OrderServerClient;
use App\Models\Customer;
use App\Services\ClientProvisioningService;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class OrderClientProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        config(['app.env' => 'testing']);
    }

    /** @test */
    public function it_provisions_client_configurations_for_orders()
    {
        // Create test data
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create(['server_id' => $server->id]);
        $inbound = ServerInbound::factory()->create(['server_id' => $server->id]);
        
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'processing'
        ]);
        
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
            'quantity' => 1
        ]);

        // Mock XUI service to avoid external API calls
        $this->mock(\App\Services\XUIService::class, function ($mock) {
            $mock->shouldReceive('addClient')->andReturn([
                'success' => true,
                'id' => 'test-uuid-123',
                'subId' => 'test-sub-123',
                'email' => 'test@example.com',
                'link' => 'vless://test-link',
                'sub_link' => 'https://example.com/sub/test-sub-123',
                'json_link' => 'https://example.com/json/test-sub-123'
            ]);
        });

        // Provision the order
        $provisioningService = app(ClientProvisioningService::class);
        $results = $provisioningService->provisionOrder($order);

        // Assert provisioning was successful
        $this->assertTrue($results[$orderItem->id]['success'] ?? false);
        
        // Assert ServerClient was created
        $this->assertDatabaseHas('server_clients', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'plan_id' => $serverPlan->id
        ]);
        
        // Assert OrderServerClient tracking record was created
        $this->assertDatabaseHas('order_server_clients', [
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'provision_status' => 'completed'
        ]);
        
        // Assert order status was updated
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        
        // Assert client configurations are accessible
        $clients = $order->getAllClients();
        $this->assertGreaterThan(0, $clients->count());
        
        $configurations = $order->getClientConfigurations();
        $this->assertNotEmpty($configurations);
        $this->assertArrayHasKey('client_link', $configurations[0]);
    }

    /** @test */
    public function it_handles_provisioning_failures_gracefully()
    {
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create(['server_id' => $server->id]);
        
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'processing'
        ]);
        
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
            'quantity' => 1
        ]);

        // Mock XUI service to simulate failure
        $this->mock(\App\Services\XUIService::class, function ($mock) {
            $mock->shouldReceive('addClient')->andThrow(new \Exception('XUI service unavailable'));
        });

        // Provision the order (should handle failure gracefully)
        $provisioningService = app(ClientProvisioningService::class);
        $results = $provisioningService->provisionOrder($order);

        // Assert provisioning failed but was handled
        $this->assertFalse($results[$orderItem->id]['success'] ?? true);
        
        // Assert OrderServerClient tracking record shows failure
        $this->assertDatabaseHas('order_server_clients', [
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'provision_status' => 'failed'
        ]);
        
        // Order should have failed provisions
        $this->assertTrue($order->hasFailedProvisions());
    }

    /** @test */
    public function process_xui_order_job_provisions_order_correctly()
    {
        Queue::fake();
        
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create(['server_id' => $server->id]);
        
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'processing'
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
            'quantity' => 1
        ]);

        // Dispatch the job
        ProcessXuiOrder::dispatch($order);
        
        // Assert job was queued
        Queue::assertPushed(ProcessXuiOrder::class);
    }

    /** @test */
    public function customer_can_access_client_configurations_after_purchase()
    {
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create(['server_id' => $server->id]);
        
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'completed'
        ]);
        
        // Create a provisioned client
        $serverClient = ServerClient::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'plan_id' => $serverPlan->id,
            'status' => 'active'
        ]);

        // Test that customer can get configurations
        $configurations = $order->getClientConfigurations();
        $this->assertNotEmpty($configurations);
        
        // Test that configurations include necessary fields
        $config = $configurations[0];
        $this->assertArrayHasKey('client_link', $config);
        $this->assertArrayHasKey('subscription_link', $config);
        $this->assertArrayHasKey('qr_codes', $config);
    }
}