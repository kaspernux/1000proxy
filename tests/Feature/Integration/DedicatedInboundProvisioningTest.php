<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Jobs\ProcessXuiOrder;

class DedicatedInboundProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_dedicated_inbound_and_client()
    {
    $customer = Customer::factory()->create();
        $server = Server::factory()->create(['host' => 'dedic.example.com', 'is_active' => true]);
        $plan = ServerPlan::factory()->create([
            'name' => 'Single User Plan',
            'type' => 'single', // mapped to dedicated in service
            'days' => 30,
            'volume' => 100,
            'server_id' => $server->id,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
        $order->orderItems()->create([
            'server_plan_id' => $plan->id,
            'server_id' => $server->id,
            'quantity' => 1,
            'unit_amount' => 10,
            'total_amount' => 10,
        ]);

        Http::fake([
            '*/login' => Http::response(['success' => true, 'obj' => 'session'], 200),
            '*/panel/api/inbounds/add' => Http::response(['success' => true, 'obj' => ['id' => 12345]], 200),
            '*/panel/api/inbounds/get/*' => Http::response([
                'success' => true,
                'obj' => [
                    'id' => 12345,
                    'sniffing' => json_encode(['enabled' => false, 'destOverride' => []]),
                ],
            ], 200),
            '*/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'obj' => [
                    'id' => 'uuid-dedic-1',
                    'settings' => json_encode(['clients' => [[ 'id' => 'uuid-dedic-1', 'email' => $customer->email ]]]),
                ],
            ], 200),
        ]);

        (new ProcessXuiOrder($order))->handle();

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertDatabaseCount('server_inbounds', 1);
        $this->assertDatabaseHas('server_clients', [
            'order_id' => $order->id,
            'server_id' => $server->id,
            'uuid' => 'uuid-dedic-1',
        ]);
    }
}
