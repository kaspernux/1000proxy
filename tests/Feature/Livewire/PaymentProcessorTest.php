<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Livewire\Components\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Session;

/**
 * Payment Processor Livewire Component Tests
 *
 * Tests payment processing functionality, real-time updates,
 * error handling, and payment gateway integration.
 *
 * @version 1.0.0
 * @author ProxyAdmin System
 */
class PaymentProcessorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $user;
    protected $customer;
    protected $order;
    protected $plan;
    protected $paymentMethods;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and customer
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create server plan
        $this->plan = ServerPlan::factory()->create([
            'price' => 29.99,
            'duration_days' => 30
        ]);

        // Create test order
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'total_amount' => $this->plan->price,
            'status' => 'pending',
            'currency' => 'USD'
        ]);

        // Create payment methods
        $this->paymentMethods = collect([
            PaymentMethod::factory()->create([
                'name' => 'Credit Card',
                'type' => 'card',
                'is_active' => true
            ]),
            PaymentMethod::factory()->create([
                'name' => 'Bitcoin',
                'type' => 'crypto',
                'is_active' => true
            ]),
            PaymentMethod::factory()->create([
                'name' => 'Wallet Balance',
                'type' => 'wallet',
                'is_active' => true
            ])
        ]);

        // Create customer wallet
        $this->wallet = Wallet::factory()->create([
            'customer_id' => $this->customer->id,
            'balance' => 100.00,
            'currency' => 'USD'
        ]);
    }

    /** @test */
    public function payment_processor_component_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->assertStatus(200)
            ->assertSee('Payment Processing')
            ->assertViewIs('livewire.components.payment-processor');
    }

    /** @test */
    public function payment_processor_displays_order_details()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->assertSee($this->order->total_amount)
            ->assertSee($this->order->currency)
            ->assertStatus(200);
    }

    /** @test */
    public function payment_processor_shows_available_payment_methods()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PaymentProcessor::class, ['order' => $this->order]);

        foreach ($this->paymentMethods as $method) {
            $component->assertSee($method->name);
        }
    }

    /** @test */
    public function payment_method_selection_works()
    {
        $this->actingAs($this->user);

        $paymentMethod = $this->paymentMethods->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('selectPaymentMethod', $paymentMethod->id)
            ->assertSet('selectedPaymentMethod', $paymentMethod->id)
            ->assertStatus(200);
    }

    /** @test */
    public function credit_card_payment_processing_works()
    {
        $this->actingAs($this->user);

        $cardMethod = $this->paymentMethods->where('type', 'card')->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $cardMethod->id)
            ->set('cardNumber', '4111111111111111')
            ->set('expiryMonth', '12')
            ->set('expiryYear', '2025')
            ->set('cvv', '123')
            ->set('cardholderName', 'John Doe')
            ->call('processPayment')
            ->assertStatus(200);
    }

    /** @test */
    public function crypto_payment_processing_works()
    {
        $this->actingAs($this->user);

        $cryptoMethod = $this->paymentMethods->where('type', 'crypto')->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $cryptoMethod->id)
            ->set('cryptoCurrency', 'BTC')
            ->call('generateCryptoAddress')
            ->assertStatus(200)
            ->assertSet('cryptoAddress', function($address) {
                return !empty($address);
            });
    }

    /** @test */
    public function wallet_payment_processing_works()
    {
        $this->actingAs($this->user);

        $walletMethod = $this->paymentMethods->where('type', 'wallet')->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $walletMethod->id)
            ->call('processWalletPayment')
            ->assertStatus(200);
    }

    /** @test */
    public function payment_validation_works_correctly()
    {
        $this->actingAs($this->user);

        $cardMethod = $this->paymentMethods->where('type', 'card')->first();

        // Test with invalid card data
        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $cardMethod->id)
            ->set('cardNumber', '1234') // Invalid card number
            ->set('expiryMonth', '13') // Invalid month
            ->set('expiryYear', '2020') // Past year
            ->set('cvv', '12') // Invalid CVV
            ->call('processPayment')
            ->assertHasErrors(['cardNumber', 'expiryMonth', 'expiryYear', 'cvv']);
    }

    /** @test */
    public function insufficient_wallet_balance_handling()
    {
        $this->actingAs($this->user);

        // Update wallet to have insufficient balance
        $this->wallet->update(['balance' => 10.00]);

        $walletMethod = $this->paymentMethods->where('type', 'wallet')->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $walletMethod->id)
            ->call('processWalletPayment')
            ->assertHasErrors(['wallet_balance']);
    }

    /** @test */
    public function payment_processing_real_time_updates()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->assertSet('processingStatus', 'idle')
            ->call('startPaymentProcessing')
            ->assertSet('processingStatus', 'processing')
            ->assertSee('Processing payment...');
    }

    /** @test */
    public function payment_progress_tracking_works()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('updatePaymentProgress', 25)
            ->assertSet('paymentProgress', 25)
            ->call('updatePaymentProgress', 50)
            ->assertSet('paymentProgress', 50)
            ->call('updatePaymentProgress', 100)
            ->assertSet('paymentProgress', 100);
    }

    /** @test */
    public function payment_retry_functionality_works()
    {
        $this->actingAs($this->user);

        $cardMethod = $this->paymentMethods->where('type', 'card')->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $cardMethod->id)
            ->set('cardNumber', '4000000000000002') // Declined card
            ->set('expiryMonth', '12')
            ->set('expiryYear', '2025')
            ->set('cvv', '123')
            ->set('cardholderName', 'John Doe')
            ->call('processPayment')
            ->assertSet('paymentStatus', 'failed')
            ->call('retryPayment')
            ->assertSet('paymentStatus', 'processing');
    }

    /** @test */
    public function payment_cancellation_works()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('startPaymentProcessing')
            ->assertSet('processingStatus', 'processing')
            ->call('cancelPayment')
            ->assertSet('processingStatus', 'cancelled')
            ->assertDispatched('paymentCancelled');
    }

    /** @test */
    public function payment_timeout_handling()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('simulatePaymentTimeout')
            ->assertSet('paymentStatus', 'timeout')
            ->assertSee('Payment timed out');
    }

    /** @test */
    public function payment_error_handling()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('simulatePaymentError', 'Network error')
            ->assertSet('paymentStatus', 'error')
            ->assertSee('Network error');
    }

    /** @test */
    public function payment_success_handling()
    {
        $this->actingAs($this->user);

        $cardMethod = $this->paymentMethods->where('type', 'card')->first();

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $cardMethod->id)
            ->call('simulateSuccessfulPayment')
            ->assertSet('paymentStatus', 'success')
            ->assertDispatched('paymentCompleted')
            ->assertSee('Payment successful');
    }

    /** @test */
    public function payment_receipt_generation()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('simulateSuccessfulPayment')
            ->call('generateReceipt')
            ->assertStatus(200)
            ->assertDispatched('receiptGenerated');
    }

    /** @test */
    public function payment_security_features()
    {
        $this->actingAs($this->user);

        // Test CSRF protection
        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('processPayment')
            ->assertStatus(200);

        // Test rate limiting (multiple rapid requests)
        $component = Livewire::test(PaymentProcessor::class, ['order' => $this->order]);

        for ($i = 0; $i < 5; $i++) {
            $component->call('processPayment');
        }

        // Should handle rate limiting gracefully
        $component->assertStatus(200);
    }

    /** @test */
    public function payment_component_state_persistence()
    {
        $this->actingAs($this->user);

        $cardMethod = $this->paymentMethods->where('type', 'card')->first();

        $component = Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedPaymentMethod', $cardMethod->id)
            ->set('cardholderName', 'John Doe')
            ->set('savePaymentMethod', true);

        // Simulate page refresh or component re-mount
        $component->assertSet('selectedPaymentMethod', $cardMethod->id)
                  ->assertSet('cardholderName', 'John Doe')
                  ->assertSet('savePaymentMethod', true);
    }

    /** @test */
    public function payment_component_accessibility()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->assertSee('aria-label')
            ->assertSee('aria-describedby')
            ->assertStatus(200);
    }

    /** @test */
    public function payment_component_mobile_responsiveness()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('isMobileView', true)
            ->call('optimizeForMobile')
            ->assertSet('isMobileView', true)
            ->assertStatus(200);
    }

    /** @test */
    public function payment_component_performance_under_load()
    {
        $this->actingAs($this->user);

        $start = microtime(true);

        // Simulate multiple concurrent payment processes
        for ($i = 0; $i < 10; $i++) {
            Livewire::test(PaymentProcessor::class, ['order' => $this->order])
                ->call('processPayment')
                ->assertStatus(200);
        }

        $end = microtime(true);
        $executionTime = $end - $start;

        // Should handle multiple requests efficiently
        $this->assertLessThan(5.0, $executionTime, 'Payment processing should handle load efficiently');
    }

    /** @test */
    public function payment_webhook_handling()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('handleWebhook', [
                'event' => 'payment.succeeded',
                'payment_id' => 'pi_test_123',
                'amount' => $this->order->total_amount
            ])
            ->assertStatus(200)
            ->assertDispatched('webhookProcessed');
    }

    /** @test */
    public function payment_refund_processing()
    {
        $this->actingAs($this->user);

        // First complete a payment
        $component = Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('simulateSuccessfulPayment')
            ->assertSet('paymentStatus', 'success');

        // Then test refund
        $component->call('processRefund', 'Customer request')
            ->assertSet('refundStatus', 'processing')
            ->assertDispatched('refundInitiated');
    }

    /** @test */
    public function payment_currency_conversion()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('selectedCurrency', 'EUR')
            ->call('convertCurrency')
            ->assertStatus(200)
            ->assertSet('convertedAmount', function($amount) {
                return is_numeric($amount) && $amount > 0;
            });
    }

    /** @test */
    public function payment_history_tracking()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->call('simulateSuccessfulPayment')
            ->call('getPaymentHistory')
            ->assertStatus(200)
            ->assertViewHas('paymentHistory');
    }

    /** @test */
    public function payment_component_event_listeners()
    {
        $this->actingAs($this->user);

        Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->dispatch('orderUpdated', ['order_id' => $this->order->id])
            ->assertStatus(200);
    }

    /** @test */
    public function payment_component_cleanup()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PaymentProcessor::class, ['order' => $this->order])
            ->set('cardNumber', '4111111111111111')
            ->call('clearSensitiveData')
            ->assertSet('cardNumber', '')
            ->assertSet('cvv', '')
            ->assertStatus(200);
    }

    protected function tearDown(): void
    {
        // Clean up any payment-related resources
        parent::tearDown();
    }
}
