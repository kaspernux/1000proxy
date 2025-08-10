<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Order, OrderItem, ServerPlan, Server, ServerInbound, Customer};
use App\Jobs\ProcessXuiOrder;
use App\Services\ClientProvisioningService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class ProvisioningModesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Minimal seeds / factories assumed to exist
    }

    /** @test */
    public function shared_plan_uses_existing_inbound()
    {
        $server = Server::factory()->create(['auto_provisioning' => true, 'status' => 'up', 'health_status' => 'healthy']);
        $inbound = ServerInbound::factory()->create(['server_id' => $server->id, 'provisioning_enabled' => true, 'status' => 'active', 'capacity' => 100]);
        $plan = ServerPlan::factory()->create(['server_id' => $server->id, 'type' => 'multiple', 'preferred_inbound_id' => $inbound->id, 'is_active' => true, 'in_stock' => true, 'on_sale' => true]);
        $order = Order::factory()->create(['payment_status' => 'paid']);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'server_plan_id' => $plan->id, 'quantity' => 1]);

        $service = app(ClientProvisioningService::class);
        Log::shouldReceive('info');
        Log::shouldReceive('error');

        $results = $service->provisionOrder($order->fresh('items.serverPlan'));

        $this->assertTrue($order->fresh()->isFullyProvisioned());
        $this->assertEquals($inbound->id, $order->orderServerClients()->first()->server_inbound_id);
    }

    /** @test */
    public function dedicated_plan_creates_new_inbound()
    {
        $server = Server::factory()->create(['auto_provisioning' => true, 'status' => 'up', 'health_status' => 'healthy']);
        $templateInbound = ServerInbound::factory()->create(['server_id' => $server->id, 'provisioning_enabled' => true, 'status' => 'active', 'capacity' => 100]);
        $plan = ServerPlan::factory()->create(['server_id' => $server->id, 'type' => 'single', 'preferred_inbound_id' => $templateInbound->id, 'is_active' => true, 'in_stock' => true, 'on_sale' => true]);
        $order = Order::factory()->create(['payment_status' => 'paid']);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'server_plan_id' => $plan->id, 'quantity' => 1]);

        // Mock XUI interactions by faking addClient & createInbound via partial mocking if needed
        $service = app(ClientProvisioningService::class);
        Log::shouldReceive('info');
        Log::shouldReceive('error');

        $results = $service->provisionOrder($order->fresh('items.serverPlan'));

        $osc = $order->orderServerClients()->first();
        $this->assertNotNull($osc->dedicated_inbound_id);
        $this->assertNotEquals($templateInbound->id, $osc->dedicated_inbound_id, 'Dedicated inbound should differ from template inbound');
    }
}
