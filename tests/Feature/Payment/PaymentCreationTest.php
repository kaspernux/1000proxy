<?php

namespace Tests\Feature\Payment;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class PaymentCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Migrate if needed
        $this->artisan('migrate');
    }

    private function createCustomerWithWallet(float $balance = 0): Customer
    {
        $customer = Customer::factory()->create();
        // Use existing auto-created wallet and update instead of creating duplicate
        $wallet = $customer->wallet; // created in Customer::booted
        if ($wallet) {
            $wallet->update(['balance' => $balance, 'currency' => 'USD']);
        } else {
            $wallet = $customer->wallet()->create(['balance' => $balance, 'currency' => 'USD']);
        }
        return $customer;
    }

    public function test_wallet_payment_insufficient_balance()
    {
    $customer = $this->createCustomerWithWallet(5); // low balance
    Sanctum::actingAs($customer, ['*']);

    $response = $this->postJson('/api/payment/create', [
            'amount' => 50,
            'currency' => 'USD',
            'gateway' => 'wallet'
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_wallet_payment_success()
    {
    $customer = $this->createCustomerWithWallet(100); // enough balance
    Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/payment/create', [
            'amount' => 25,
            'currency' => 'USD',
            'gateway' => 'wallet'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
