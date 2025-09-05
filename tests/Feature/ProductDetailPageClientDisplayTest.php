<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\Customer;
use App\Livewire\ProductDetailPage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductDetailPageClientDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function product_detail_page_shows_purchased_configurations_for_authenticated_customers()
    {
        // Create test data
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-plan'
        ]);
        
        // Create a completed order with provisioned client
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'completed'
        ]);
        
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'server_plan_id' => $serverPlan->id,
            'quantity' => 1
        ]);
        
        $serverClient = ServerClient::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'plan_id' => $serverPlan->id,
            'status' => 'active',
            'client_link' => 'vless://test-client-link',
            'remote_sub_link' => 'https://example.com/sub_json/test',
            'remote_json_link' => 'https://example.com/proxy_json/test'
        ]);

        // Test as authenticated customer
        $this->actingAs($customer, 'customer');
        
        $component = Livewire::test(ProductDetailPage::class, ['slug' => 'test-plan']);
        
        // Check if the component shows purchased configurations
        $component->assertSee('Your Active Configurations')
                  ->assertSee('Configuration #1')
                  ->assertSee('vless://test-client-link')
                  ->assertSee('Manage Servers')
                  ->assertSet('customerOwnsPlan', true)
                  ->assertSet('customerConfigurations', function ($configs) {
                      return count($configs) > 0 && !empty($configs[0]['client_link']);
                  });
    }

    /** @test */
    public function product_detail_page_shows_purchase_options_for_non_authenticated_users()
    {
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-plan'
        ]);

        // Test as guest user
        $component = Livewire::test(ProductDetailPage::class, ['slug' => 'test-plan']);
        
        // Should show purchase options, not configurations
        $component->assertSee('Add to Cart');
        $component->assertDontSee('Your Configuration'); // Assuming this would be the section title
    }

    /** @test */
    public function product_detail_page_shows_purchase_options_for_customers_without_this_plan()
    {
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create([
            'server_id' => $server->id,
            'slug' => 'test-plan'
        ]);

        // Customer has no orders for this plan
        $this->actingAs($customer, 'customer');
        
        $component = Livewire::test(ProductDetailPage::class, ['slug' => 'test-plan']);
        
        // Should still show purchase options since customer doesn't own this plan
        $component->assertSee('Add to Cart');
    }

    /** @test */
    public function customer_can_access_configurations_through_dashboard()
    {
        $customer = Customer::factory()->create();
        $server = Server::factory()->create();
        $serverPlan = ServerPlan::factory()->create(['server_id' => $server->id]);
        
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
            'status' => 'completed'
        ]);
        
        $serverClient = ServerClient::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'plan_id' => $serverPlan->id,
            'status' => 'active'
        ]);

        $this->actingAs($customer, 'customer');
        
        // Test customer dashboard pages
        $response = $this->get('/customer/orders');
        $response->assertStatus(200);
        
        $response = $this->get('/customer/my-active-servers');
        $response->assertStatus(200);
        
        // These pages should show the client configurations
        // If they don't, that's where the real issue is
    }
}