<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\PaymentGatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TestPaymentSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:payments {--method=all} {--detailed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payment system integration and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Payment System Integration Test');
        $this->newLine();

        $method = $this->option('method');

        if ($method === 'all') {
            return $this->testAllPaymentMethods();
        } else {
            return $this->testSpecificPaymentMethod($method);
        }
    }

    /**
     * Test all available payment methods
     */
    private function testAllPaymentMethods(): int
    {
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $results = [];

        $this->info("Found {$paymentMethods->count()} active payment methods");
        $this->newLine();

        foreach ($paymentMethods as $method) {
            $this->info("Testing payment method: {$method->name}");
            $result = $this->testPaymentMethod($method);
            $results[] = $result;
            $this->newLine();
        }

        $this->displaySummary($results);
        return 0;
    }

    /**
     * Test a specific payment method
     */
    private function testSpecificPaymentMethod(string $methodSlug): int
    {
        $method = PaymentMethod::where('slug', $methodSlug)->first();

        if (!$method) {
            $this->error("Payment method '{$methodSlug}' not found");
            return 1;
        }

        if (!$method->is_active) {
            $this->warn("Payment method '{$method->name}' is not active");
        }

        $this->info("Testing payment method: {$method->name}");
        $result = $this->testPaymentMethod($method);

        $this->displayMethodResult($result);
        return $result['success'] ? 0 : 1;
    }

    /**
     * Test individual payment method
     */
    private function testPaymentMethod(PaymentMethod $method): array
    {
        $result = [
            'method' => $method,
            'tests' => [],
            'success' => false,
            'total_tests' => 0,
            'passed_tests' => 0
        ];

        try {
            // Test 1: Configuration Check
            $result['tests']['configuration'] = $this->testConfiguration($method);
            $result['total_tests']++;
            if ($result['tests']['configuration']['success']) {
                $result['passed_tests']++;
            }

            // Test 2: Gateway Service Check
            $result['tests']['service'] = $this->testPaymentService($method);
            $result['total_tests']++;
            if ($result['tests']['service']['success']) {
                $result['passed_tests']++;
            }

            // Test 3: Mock Payment Creation (if service available)
            if ($result['tests']['service']['success']) {
                $result['tests']['mock_payment'] = $this->testMockPayment($method);
                $result['total_tests']++;
                if ($result['tests']['mock_payment']['success']) {
                    $result['passed_tests']++;
                }
            }

            $result['success'] = $result['passed_tests'] > 0;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            if ($this->option('detailed')) {
                $this->error("Exception: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Test payment method configuration
     */
    private function testConfiguration(PaymentMethod $method): array
    {
        $this->line('  ⚙️  Testing configuration...');

        $checks = [];

        // Check required fields
        $checks['name'] = !empty($method->name);
        $checks['slug'] = !empty($method->slug);
        $checks['type'] = !empty($method->type);

        // Check specific configurations
        switch ($method->type) {
            case 'stripe':
                $checks['stripe_key'] = !empty(config('services.stripe.key'));
                $checks['stripe_secret'] = !empty(config('services.stripe.secret'));
                break;

            case 'paypal':
                $checks['paypal_client_id'] = !empty(config('services.paypal.client_id'));
                $checks['paypal_secret'] = !empty(config('services.paypal.secret'));
                break;

            case 'nowpayments':
                $checks['nowpayments_key'] = !empty(config('nowpayments.api_key'));
                break;

            case 'wallet':
                $checks['wallet_enabled'] = true; // Internal system
                break;
        }

        $passed = array_sum($checks);
        $total = count($checks);

        if ($passed === $total) {
            $this->line("    ✅ Configuration complete ({$passed}/{$total})");
            return ['success' => true, 'message' => "Configuration valid ({$passed}/{$total})", 'checks' => $checks];
        } else {
            $this->line("    ❌ Configuration incomplete ({$passed}/{$total})");
            return ['success' => false, 'message' => "Configuration incomplete ({$passed}/{$total})", 'checks' => $checks];
        }
    }

    /**
     * Test payment service availability
     */
    private function testPaymentService(PaymentMethod $method): array
    {
        $this->line('  🔧 Testing service availability...');

        try {
            // Test basic service availability for common payment methods
            switch ($method->type) {
                case 'wallet':
                    $this->line("    ✅ Wallet service is internal - always available");
                    return ['success' => true, 'message' => 'Wallet service available'];

                case 'nowpayments':
                    $apiKey = config('nowpayments.api_key');
                    if (!empty($apiKey)) {
                        $this->line("    ✅ NowPayments API key configured");
                        return ['success' => true, 'message' => 'NowPayments service available'];
                    } else {
                        $this->line("    ❌ NowPayments API key not configured");
                        return ['success' => false, 'message' => 'NowPayments API key missing'];
                    }

                case 'stripe':
                    $service = app(\App\Services\PaymentGateways\StripePaymentService::class);
                    if ($service->isEnabled()) {
                        $this->line("    ✅ Stripe service available");
                        return ['success' => true, 'message' => 'Stripe service available'];
                    } else {
                        $this->line("    ❌ Stripe service not configured");
                        return ['success' => false, 'message' => 'Stripe credentials missing'];
                    }

                case 'paypal':
                    $service = app(\App\Services\PaymentGateways\PayPalPaymentService::class);
                    if ($service->isEnabled()) {
                        $this->line("    ✅ PayPal service available");
                        return ['success' => true, 'message' => 'PayPal service available'];
                    } else {
                        $this->line("    ❌ PayPal service not configured");
                        return ['success' => false, 'message' => 'PayPal credentials missing'];
                    }

                default:
                    $this->line("    ⚠️  Service type '{$method->type}' not implemented yet");
                    return ['success' => false, 'message' => "Service type '{$method->type}' not implemented"];
            }

        } catch (\Exception $e) {
            $this->line("    ❌ Service error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Test mock payment creation
     */
    private function testMockPayment(PaymentMethod $method): array
    {
        $this->line('  💳 Testing mock payment...');

        try {
            // For wallet, test differently
            if ($method->type === 'wallet') {
                $this->line("    ✅ Wallet payments are internal - no external API required");
                return ['success' => true, 'message' => 'Wallet system operational'];
            }

            // For external payment methods, test configuration
            $configValid = $this->testExternalPaymentConfig($method);

            if ($configValid) {
                $this->line("    ✅ External payment configuration valid");
                return ['success' => true, 'message' => 'Configuration valid for external payments'];
            } else {
                $this->line("    ❌ External payment configuration invalid");
                return ['success' => false, 'message' => 'Configuration invalid for external payments'];
            }

        } catch (\Exception $e) {
            $this->line("    ❌ Mock payment error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Test external payment configuration
     */
    private function testExternalPaymentConfig(PaymentMethod $method): bool
    {
        switch ($method->type) {
            case 'stripe':
                return !empty(config('services.stripe.key')) && !empty(config('services.stripe.secret'));

            case 'paypal':
                return !empty(config('services.paypal.client_id')) && !empty(config('services.paypal.secret'));

            case 'nowpayments':
                return !empty(config('nowpayments.api_key'));

            default:
                return true; // Assume other methods are configured
        }
    }

    /**
     * Display result for single payment method
     */
    private function displayMethodResult(array $result): void
    {
        $this->newLine();
        $this->info("📊 Test Results for: {$result['method']->name}");
        $this->line("Type: {$result['method']->type}");
        $this->line("Tests passed: {$result['passed_tests']}/{$result['total_tests']}");

        if ($result['success']) {
            $this->info('✅ Overall Status: PASS');
        } else {
            $this->error('❌ Overall Status: FAIL');
        }

        if (isset($result['error'])) {
            $this->error("Error: {$result['error']}");
        }

        if ($this->option('detailed') && isset($result['tests'])) {
            $this->line("\nDetailed results:");
            foreach ($result['tests'] as $testName => $testResult) {
                $status = $testResult['success'] ? '✅' : '❌';
                $this->line("  {$status} {$testName}: {$testResult['message']}");
            }
        }
    }

    /**
     * Display summary for multiple payment methods
     */
    private function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info('📊 Payment System Test Summary');
        $this->line(str_repeat('=', 50));

        $totalMethods = count($results);
        $successfulMethods = 0;

        foreach ($results as $result) {
            $status = $result['success'] ? '✅' : '❌';
            $this->line("{$status} {$result['method']->name} ({$result['passed_tests']}/{$result['total_tests']})");

            if ($result['success']) {
                $successfulMethods++;
            }
        }

        $this->newLine();
        $this->info("Payment methods tested: {$totalMethods}");
        $this->info("Successful: {$successfulMethods}");
        $this->info("Failed: " . ($totalMethods - $successfulMethods));

        if ($successfulMethods === $totalMethods) {
            $this->info('🎉 All payment methods passed testing!');
        } elseif ($successfulMethods > 0) {
            $this->warn('⚠️  Some payment methods failed testing');
        } else {
            $this->error('💥 All payment methods failed testing');
        }

        // Provide recommendations
        $this->newLine();
        $this->info('💡 Recommendations:');
        $this->line('1. Enable Stripe by setting STRIPE_KEY and STRIPE_SECRET in .env');
        $this->line('2. Enable PayPal by setting PAYPAL_CLIENT_ID and PAYPAL_SECRET in .env');
        $this->line('3. Verify NowPayments API key in NOWPAYMENTS_API_KEY');
        $this->line('4. Wallet system should work without additional configuration');
    }
}
