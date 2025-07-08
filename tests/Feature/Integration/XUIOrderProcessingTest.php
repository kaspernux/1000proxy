<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Models\Server;
use App\Jobs\ProcessXuiOrder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class XUIOrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected ServerPlan $serverPlan;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'host' => 'test-server.com',
            'port' => 443,
            'username' => 'admin',
            'password' => 'password',
            'is_active' => true,
        ]);
        
        $this->serverPlan = ServerPlan::factory()->create([
            'name' => 'Premium Plan',
            'price' => 99.99,
            'duration_days' => 30,
            'max_connections' => 5,
            'bandwidth_limit_gb' => 100,
        ]);

        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 99.99,
            'grand_amount' => 99.99,
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
    }

    public function test_complete_order_to_client_creation_workflow()
    {
        Queue::fake();

        // Mock XUI panel authentication
        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => true,
                'msg' => 'Login successful',
                'obj' => 'session_cookie_here'
            ], 200),
            
            'test-server.com/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'msg' => 'Client added successfully',
                'obj' => [
                    'id' => 'uuid-123',
                    'settings' => json_encode([
                        'clients' => [[
                            'id' => 'uuid-123',
                            'email' => $this->user->email,
                            'limitIp' => 5,
                            'totalGB' => 107374182400, // 100GB
                            'expiryTime' => now()->addDays(30)->timestamp * 1000,
                            'enable' => true,
                        ]]
                    ])
                ]
            ], 200)
        ]);

        // Create order item
        $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        // Dispatch the job
        ProcessXuiOrder::dispatch($this->order);

        // Assert job was queued
        Queue::assertPushed(ProcessXuiOrder::class);
    }

    public function test_xui_client_creation_success()
    {
        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => true,
                'obj' => 'session_cookie'
            ], 200),
            
            'test-server.com/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'msg' => 'Client added successfully',
                'obj' => [
                    'id' => 'uuid-123',
                    'settings' => json_encode([
                        'clients' => [[
                            'id' => 'uuid-123',
                            'email' => $this->user->email,
                        ]]
                    ])
                ]
            ], 200)
        ]);

        $orderItem = $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        // Process the order
        $job = new ProcessXuiOrder($this->order);
        $job->handle();

        // Check if ServerClient was created
        $this->assertDatabaseHas('server_clients', [
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'order_id' => $this->order->id,
            'uuid' => 'uuid-123',
            'email' => $this->user->email,
            'is_active' => true,
        ]);

        // Check if order status was updated
        $this->order->refresh();
        $this->assertEquals('completed', $this->order->status);
    }

    public function test_xui_authentication_failure_handling()
    {
        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => false,
                'msg' => 'Invalid credentials'
            ], 401)
        ]);

        $orderItem = $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        $job = new ProcessXuiOrder($this->order);
        
        // Expect exception due to authentication failure
        $this->expectException(\Exception::class);
        $job->handle();

        // Order should remain in processing status
        $this->order->refresh();
        $this->assertEquals('processing', $this->order->status);
    }

    public function test_xui_client_creation_failure_handling()
    {
        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => true,
                'obj' => 'session_cookie'
            ], 200),
            
            'test-server.com/panel/api/inbounds/addClient' => Http::response([
                'success' => false,
                'msg' => 'Failed to add client'
            ], 400)
        ]);

        $orderItem = $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        $job = new ProcessXuiOrder($this->order);
        
        // Expect exception due to client creation failure
        $this->expectException(\Exception::class);
        $job->handle();

        // No ServerClient should be created
        $this->assertDatabaseMissing('server_clients', [
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'order_id' => $this->order->id,
        ]);
    }

    public function test_subscription_link_generation()
    {
        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => true,
                'obj' => 'session_cookie'
            ], 200),
            
            'test-server.com/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'obj' => [
                    'id' => 'uuid-123',
                    'settings' => json_encode([
                        'clients' => [[
                            'id' => 'uuid-123',
                            'email' => $this->user->email,
                        ]]
                    ])
                ]
            ], 200)
        ]);

        $orderItem = $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        $job = new ProcessXuiOrder($this->order);
        $job->handle();

        $serverClient = $this->order->serverClients()->first();
        
        $this->assertNotNull($serverClient);
        $this->assertNotEmpty($serverClient->subscription_link);
        $this->assertStringStartsWith('vless://', $serverClient->subscription_link);
        $this->assertStringContainsString('uuid-123', $serverClient->subscription_link);
        $this->assertStringContainsString('test-server.com', $serverClient->subscription_link);
    }

    public function test_qr_code_generation()
    {
        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => true,
                'obj' => 'session_cookie'
            ], 200),
            
            'test-server.com/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'obj' => [
                    'id' => 'uuid-123',
                    'settings' => json_encode([
                        'clients' => [[
                            'id' => 'uuid-123',
                            'email' => $this->user->email,
                        ]]
                    ])
                ]
            ], 200)
        ]);

        $orderItem = $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        $job = new ProcessXuiOrder($this->order);
        $job->handle();

        $serverClient = $this->order->serverClients()->first();
        
        $this->assertNotNull($serverClient);
        $this->assertNotEmpty($serverClient->qr_code);
        $this->assertStringStartsWith('data:image/png;base64,', $serverClient->qr_code);
    }

    public function test_multiple_server_plans_in_single_order()
    {
        $secondServerPlan = ServerPlan::factory()->create([
            'name' => 'Basic Plan',
            'price' => 49.99,
        ]);

        $secondServer = Server::factory()->create([
            'host' => 'second-server.com',
            'is_active' => true,
        ]);

        Http::fake([
            '*/login' => Http::response([
                'success' => true,
                'obj' => 'session_cookie'
            ], 200),
            
            '*/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'obj' => [
                    'id' => 'uuid-456',
                    'settings' => json_encode([
                        'clients' => [[
                            'id' => 'uuid-456',
                            'email' => $this->user->email,
                        ]]
                    ])
                ]
            ], 200)
        ]);

        // Create multiple order items
        $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        $this->order->orderItems()->create([
            'server_plan_id' => $secondServerPlan->id,
            'server_id' => $secondServer->id,
            'quantity' => 1,
            'unit_amount' => 49.99,
            'total_amount' => 49.99,
        ]);

        $job = new ProcessXuiOrder($this->order);
        $job->handle();

        // Check that clients were created for both servers
        $this->assertCount(2, $this->order->serverClients);
        
        $this->assertDatabaseHas('server_clients', [
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'order_id' => $this->order->id,
        ]);

        $this->assertDatabaseHas('server_clients', [
            'user_id' => $this->user->id,
            'server_id' => $secondServer->id,
            'order_id' => $this->order->id,
        ]);
    }

    public function test_order_processing_sends_email_notification()
    {
        \Illuminate\Support\Facades\Mail::fake();

        Http::fake([
            'test-server.com/login' => Http::response([
                'success' => true,
                'obj' => 'session_cookie'
            ], 200),
            
            'test-server.com/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'obj' => [
                    'id' => 'uuid-123',
                    'settings' => json_encode([
                        'clients' => [[
                            'id' => 'uuid-123',
                            'email' => $this->user->email,
                        ]]
                    ])
                ]
            ], 200)
        ]);

        $orderItem = $this->order->orderItems()->create([
            'server_plan_id' => $this->serverPlan->id,
            'server_id' => $this->server->id,
            'quantity' => 1,
            'unit_amount' => 99.99,
            'total_amount' => 99.99,
        ]);

        $job = new ProcessXuiOrder($this->order);
        $job->handle();

        // Check that order completion email was sent
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\OrderPlaced::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }
}
