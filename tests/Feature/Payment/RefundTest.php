<?php

namespace Tests\Feature\Payment;

use Tests\TestCase;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_refund_nonexistent_transaction()
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer');

        $response = $this->postJson('/api/payment/refund', [
            'transaction_id' => 'does-not-exist'
        ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }
}
