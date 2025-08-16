<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerPlan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customer = Customer::factory()->create();
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'grand_amount' => 99.99,
            'payment_status' => 'pending',
        ]);
    }

    public function test_authenticated_user_can_create_crypto_payment()
    {
        Http::fake([
            'nowpayments.io/v1/payment' => Http::response([
                'payment_id' => 'payment_123',
                'payment_status' => 'waiting',
                'pay_address' => 'crypto_address_123',
                'price_amount' => 99.99,
                'price_currency' => 'usd',
                'pay_amount' => 0.001,
                'pay_currency' => 'btc',
            ], 200)
        ]);

    $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/crypto', [
                'order_id' => $this->order->id,
                'payment_method' => 'crypto',
                'currency' => 'BTC',
                'amount' => 99.99,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment_id',
                    'payment_status',
                    'pay_address',
                ]
            ]);
    }

    public function test_user_cannot_create_payment_for_others_order()
    {
    $otherCustomer = Customer::factory()->create();
    $otherOrder = Order::factory()->create(['customer_id' => $otherCustomer->id]);

    $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/crypto', [
                'order_id' => $otherOrder->id,
                'payment_method' => 'crypto',
                'currency' => 'BTC',
                'amount' => 99.99,
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_payment()
    {
        $response = $this->postJson('/api/payments/crypto', [
            'order_id' => $this->order->id,
            'payment_method' => 'crypto',
            'currency' => 'BTC',
            'amount' => 99.99,
        ]);

        $response->assertStatus(401);
    }

    public function test_create_payment_validates_required_fields()
    {
    $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/crypto', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id', 'payment_method', 'currency', 'amount']);
    }

    public function test_create_payment_validates_currency()
    {
    $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/crypto', [
                'order_id' => $this->order->id,
                'payment_method' => 'crypto',
                'currency' => 'INVALID',
                'amount' => 99.99,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    public function test_user_can_get_payment_status()
    {
        Http::fake([
            'nowpayments.io/v1/payment/status/*' => Http::response([
                'payment_id' => 'payment_123',
                'payment_status' => 'confirmed',
                'pay_amount' => 0.001,
                'actually_paid' => 0.001,
            ], 200)
        ]);

        $this->order->invoice()->create([
            'payment_id' => 'payment_123',
            'invoice_url' => 'https://example.com',
            'payment_status' => 'pending',
            'price_amount' => 99.99,
            'price_currency' => 'BTC',
            'customer_id' => $this->order->customer_id,
            'payment_method_id' => \App\Models\PaymentMethod::factory()->create()->id,
        ]);

    $response = $this->actingAs($this->customer, 'customer')
            ->getJson("/api/payments/status/payment_123");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment_id',
                    'payment_status',
                ]
            ]);
    }

    public function test_user_cannot_get_payment_status_for_others_payment()
    {
    $otherCustomer = Customer::factory()->create();
    $otherOrder = Order::factory()->create(['customer_id' => $otherCustomer->id]);
        
        $otherOrder->invoice()->create([
            'payment_id' => 'payment_123',
            'invoice_url' => 'https://example.com',
            'payment_status' => 'pending',
            'price_amount' => 99.99,
            'price_currency' => 'BTC',
            'customer_id' => $otherOrder->customer_id,
            'payment_method_id' => \App\Models\PaymentMethod::factory()->create()->id,
        ]);

    $response = $this->actingAs($this->customer, 'customer')
            ->getJson("/api/payments/status/payment_123");

        $response->assertStatus(403);
    }

    public function test_get_currencies_returns_cached_data()
    {
        Http::fake([
            'nowpayments.io/v1/currencies' => Http::response([
                'currencies' => ['BTC', 'ETH', 'XMR', 'LTC']
            ], 200)
        ]);

    $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/api/payments/currencies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'currencies'
                ]
            ]);
    }

    public function test_get_estimate_price_validates_input()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/estimate', [
                'amount' => 'invalid',
                'currency_from' => 'INVALID',
                'currency_to' => 'INVALID',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'currency_from', 'currency_to']);
    }

    public function test_get_estimate_price_with_valid_data()
    {
        Http::fake([
            'nowpayments.io/v1/estimate' => Http::response([
                'currency_from' => 'usd',
                'amount_from' => 100,
                'currency_to' => 'btc',
                'estimated_amount' => 0.00234,
            ], 200)
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/estimate', [
                'amount' => 100,
                'currency_from' => 'USD',
                'currency_to' => 'BTC',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'currency_from',
                    'amount_from',
                    'currency_to',
                    'estimated_amount',
                ]
            ]);
    }

    public function test_webhook_processes_payment_confirmation()
    {
        $this->order->invoice()->create([
            'payment_id' => 'payment_123',
            'invoice_url' => 'https://example.com',
            'payment_status' => 'pending',
            'price_amount' => 99.99,
            'price_currency' => 'BTC',
            'customer_id' => $this->order->customer_id,
            'payment_method_id' => \App\Models\PaymentMethod::factory()->create()->id,
        ]);

        $webhookPayload = [
            'payment_id' => 'payment_123',
            'order_id' => $this->order->id,
            'payment_status' => 'finished',
            'price_amount' => 99.99,
            'price_currency' => 'usd',
            'pay_amount' => 0.001,
            'pay_currency' => 'btc',
        ];

        $response = $this->postJson('/api/webhooks/nowpayments', $webhookPayload, [
            'X-Nowpayments-Sig' => hash_hmac('sha256', json_encode($webhookPayload), config('services.nowpayments.webhook_secret')),
        ]);

        $response->assertStatus(200);
        
        $this->order->refresh();
        $this->assertEquals('paid', $this->order->payment_status);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $webhookPayload = [
            'payment_id' => 'payment_123',
            'order_id' => $this->order->id,
            'payment_status' => 'finished',
        ];

        $response = $this->postJson('/api/webhooks/nowpayments', $webhookPayload, [
            'X-Nowpayments-Sig' => 'invalid_signature',
        ]);

        $response->assertStatus(401);
    }

    public function test_rate_limiting_applies_to_payment_endpoints()
    {
        // Make 61 requests (exceeding the limit of 60)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->actingAs($this->customer, 'customer')
                ->postJson('/api/payments/crypto', [
                    'order_id' => $this->order->id,
                    'payment_method' => 'crypto',
                    'currency' => 'BTC',
                    'amount' => 99.99,
                ]);

            if ($i < 60) {
                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }

        // The 61st request should be rate limited
    $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/payments/crypto', [
                'order_id' => $this->order->id,
                'payment_method' => 'crypto',
                'currency' => 'BTC',
                'amount' => 99.99,
            ]);

        $response->assertStatus(429);
    }
}
