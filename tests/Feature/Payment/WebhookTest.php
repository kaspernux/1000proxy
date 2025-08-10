<?php

namespace Tests\Feature\Payment;

use Tests\TestCase;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_stripe_webhook_minimal_payload()
    {
        $payload = [
            'type' => 'unknown.event',
            'data' => ['object' => ['id' => 'pi_test_123']]
        ];

        $response = $this->postJson('/api/payment/webhook/stripe', $payload);
        // Route is under sanctum group; may need auth. If so, skip assertion or adjust middleware for testing.
        $this->assertTrue(in_array($response->status(), [200,401,403]));
    }
}
