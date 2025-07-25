<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\Wallet;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateOrderRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected ServerPlan $serverPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['is_active' => true]);
        $this->serverPlan = ServerPlan::factory()->create(['server_id' => $this->server->id]);

        // Create wallet with sufficient balance
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_create_order_with_valid_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 2,
                'duration' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'server_id',
                    'total_amount',
                    'status',
                    'server',
                    'orderItems'
                ]
            ]);
    }

    public function test_create_order_requires_server_id()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'plan_id' => $this->serverPlan->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['server_id']);
    }

    public function test_create_order_validates_server_exists()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => 999999,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['server_id']);
    }

    public function test_create_order_validates_plan_belongs_to_server()
    {
        $otherServer = Server::factory()->create(['is_active' => true]);
        $otherPlan = ServerPlan::factory()->create(['server_id' => $otherServer->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $otherPlan->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_create_order_validates_quantity_range()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 11,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_create_order_validates_duration_range()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 1,
                'duration' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 1,
                'duration' => 13,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration']);
    }

    public function test_create_order_fails_with_insufficient_balance()
    {
        // Update wallet with insufficient balance
        $this->user->wallet->update(['balance' => 1.00]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 1,
                'duration' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'required_amount',
                    'current_balance'
                ]
            ]);
    }

    public function test_create_order_fails_with_inactive_server()
    {
        $this->server->update(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'server_id' => $this->server->id,
                'plan_id' => $this->serverPlan->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Server is not available'
            ]);
    }

    public function test_unauthenticated_user_cannot_create_order()
    {
        $response = $this->postJson('/api/orders', [
            'server_id' => $this->server->id,
            'plan_id' => $this->serverPlan->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }
}
