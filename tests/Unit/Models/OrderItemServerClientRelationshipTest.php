<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerClient;
use App\Models\OrderServerClient;
use App\Models\Customer;
use App\Models\ServerPlan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderItemServerClientRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_can_access_server_clients_through_pivot_table()
    {
        // Create necessary models
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $serverPlan = ServerPlan::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
        ]);

        // Create a server client
        $serverClient = ServerClient::factory()->create([
            'order_id' => $order->id,
            'plan_id' => $serverPlan->id,
        ]);

        // Create the pivot record that links them
        OrderServerClient::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'server_client_id' => $serverClient->id,
            'provision_status' => 'completed',
        ]);

        // Test the relationship
        $this->assertNotNull($orderItem->serverClients);
        $this->assertEquals(1, $orderItem->serverClients->count());
        $this->assertEquals($serverClient->id, $orderItem->serverClients->first()->id);
    }

    public function test_order_item_server_client_accessor_returns_first_client()
    {
        // Create necessary models
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $serverPlan = ServerPlan::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
        ]);

        // Create server clients
        $serverClient1 = ServerClient::factory()->create([
            'order_id' => $order->id,
            'plan_id' => $serverPlan->id,
        ]);
        $serverClient2 = ServerClient::factory()->create([
            'order_id' => $order->id,
            'plan_id' => $serverPlan->id,
        ]);

        // Create pivot records
        OrderServerClient::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'server_client_id' => $serverClient1->id,
            'provision_status' => 'completed',
        ]);
        OrderServerClient::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'server_client_id' => $serverClient2->id,
            'provision_status' => 'completed',
        ]);

        // Test the accessor returns the first client
        $firstClient = $orderItem->server_client;
        $this->assertNotNull($firstClient);
        $this->assertInstanceOf(ServerClient::class, $firstClient);
        $this->assertTrue(in_array($firstClient->id, [$serverClient1->id, $serverClient2->id]));
    }

    public function test_order_item_server_client_accessor_returns_null_when_no_clients()
    {
        // Create order item without any server clients
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $serverPlan = ServerPlan::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
        ]);

        // Test the accessor returns null
        $this->assertNull($orderItem->server_client);
    }

    public function test_server_client_has_order_server_clients_relationship()
    {
        // Create necessary models
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $serverPlan = ServerPlan::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
        ]);

        // Create a server client
        $serverClient = ServerClient::factory()->create([
            'order_id' => $order->id,
            'plan_id' => $serverPlan->id,
        ]);

        // Create the pivot record
        OrderServerClient::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'server_client_id' => $serverClient->id,
            'provision_status' => 'completed',
        ]);

        // Test the reverse relationship
        $this->assertNotNull($serverClient->orderServerClients);
        $this->assertEquals(1, $serverClient->orderServerClients->count());
        $this->assertEquals($order->id, $serverClient->orderServerClients->first()->order_id);
    }
}