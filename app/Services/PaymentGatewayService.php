<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    protected $gateways = [
        'stripe' => \App\Services\PaymentGateways\StripePaymentService::class,
        'paypal' => \App\Services\PaymentGateways\PayPalPaymentService::class,
        'nowpayments' => \App\Services\Payment\NowPaymentsService::class, // Keep existing
        'coinbase' => \App\Services\Payment\CoinbasePaymentService::class,
        'binance' => \App\Services\Payment\BinancePaymentService::class,
        'razorpay' => \App\Services\Payment\RazorpayPaymentService::class,
        'mollie' => \App\Services\Payment\MolliePaymentService::class,
        'adyen' => \App\Services\Payment\AdyenPaymentService::class,
    ];

    /**
     * Get available payment methods for a user
     */
    public function getAvailablePaymentMethods(User $user, string $currency = 'USD'): array
    {
        $availableMethods = [];
        
        foreach ($this->gateways as $gateway => $serviceClass) {
            if ($this->isGatewayAvailable($gateway, $user->location ?? null)) {
                $service = app($serviceClass);
                
                if ($service->isEnabled() && $service->supportsCurrency($currency)) {
                    $availableMethods[] = [
                        'id' => $gateway,
                        'name' => $service->getName(),
                        'icon' => $service->getIcon(),
                        'description' => $service->getDescription(),
                        'supported_currencies' => $service->getSupportedCurrencies(),
                        'fees' => $service->getFees(),
                        'processing_time' => $service->getProcessingTime(),
                        'is_instant' => $service->isInstant(),
                    ];
                }
            }
        }
        
        return $availableMethods;
    }

    /**
     * Process payment using specified gateway
     */
    public function processPayment(string $gateway, array $paymentData): array
    {
        if (!isset($this->gateways[$gateway])) {
            throw new \InvalidArgumentException("Unsupported payment gateway: {$gateway}");
        }

        $service = app($this->gateways[$gateway]);
        
        try {
            $result = $service->processPayment($paymentData);
            
            Log::info('Payment processed successfully', [
                'gateway' => $gateway,
                'payment_id' => $result['payment_id'] ?? null,
                'amount' => $paymentData['amount'] ?? null,
                'currency' => $paymentData['currency'] ?? null,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'payment_data' => $paymentData,
            ]);
            
            throw $e;
        }
    }

    /**
     * Create payment intent for gateway
     */
    public function createPaymentIntent(string $gateway, Order $order): array
    {
        if (!isset($this->gateways[$gateway])) {
            throw new \InvalidArgumentException("Unsupported payment gateway: {$gateway}");
        }

        $service = app($this->gateways[$gateway]);
        
        return $service->createPaymentIntent([
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'order_id' => $order->id,
            'customer_email' => $order->user->email,
            'description' => "Order #{$order->id} - 1000proxy",
        ]);
    }

    /**
     * Verify payment webhook
     */
    public function verifyWebhook(string $gateway, array $payload, string $signature): bool
    {
        if (!isset($this->gateways[$gateway])) {
            return false;
        }

        $service = app($this->gateways[$gateway]);
        
        return $service->verifyWebhook($payload, $signature);
    }

    /**
     * Handle payment webhook
     */
    public function handleWebhook(string $gateway, array $payload): void
    {
        if (!isset($this->gateways[$gateway])) {
            throw new \InvalidArgumentException("Unsupported payment gateway: {$gateway}");
        }

        $service = app($this->gateways[$gateway]);
        
        $service->handleWebhook($payload);
    }

    /**
     * Check if gateway is available for user location
     */
    protected function isGatewayAvailable(string $gateway, ?string $location): bool
    {
        $restrictions = config("payment.gateway_restrictions.{$gateway}", []);
        
        if (empty($restrictions)) {
            return true;
        }
        
        if (isset($restrictions['allowed_countries']) && $location) {
            return in_array($location, $restrictions['allowed_countries']);
        }
        
        if (isset($restrictions['blocked_countries']) && $location) {
            return !in_array($location, $restrictions['blocked_countries']);
        }
        
        return true;
    }

    /**
     * Get gateway statistics
     */
    public function getGatewayStats(string $gateway = null): array
    {
        $query = PaymentMethod::query();
        
        if ($gateway) {
            $query->where('gateway', $gateway);
        }
        
        return [
            'total_transactions' => $query->count(),
            'successful_transactions' => $query->where('status', 'completed')->count(),
            'failed_transactions' => $query->where('status', 'failed')->count(),
            'pending_transactions' => $query->where('status', 'pending')->count(),
            'total_volume' => $query->where('status', 'completed')->sum('amount'),
            'average_transaction' => $query->where('status', 'completed')->avg('amount'),
        ];
    }

    /**
     * Get optimal payment method for user
     */
    public function getOptimalPaymentMethod(User $user, float $amount, string $currency = 'USD'): ?array
    {
        $availableMethods = $this->getAvailablePaymentMethods($user, $currency);
        
        if (empty($availableMethods)) {
            return null;
        }
        
        // Score each method based on various factors
        $scoredMethods = [];
        
        foreach ($availableMethods as $method) {
            $score = 0;
            
            // Favor instant methods
            if ($method['is_instant']) {
                $score += 30;
            }
            
            // Favor lower fees
            $fee = $this->calculateFee($method['fees'], $amount);
            $score += (100 - ($fee / $amount * 100));
            
            // Favor reliable gateways (based on success rate)
            $stats = $this->getGatewayStats($method['id']);
            if ($stats['total_transactions'] > 0) {
                $successRate = $stats['successful_transactions'] / $stats['total_transactions'];
                $score += $successRate * 50;
            }
            
            $scoredMethods[] = array_merge($method, ['score' => $score]);
        }
        
        // Sort by score and return the best option
        usort($scoredMethods, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scoredMethods[0] ?? null;
    }

    /**
     * Calculate fee for payment method
     */
    protected function calculateFee(array $fees, float $amount): float
    {
        $totalFee = 0;
        
        if (isset($fees['fixed'])) {
            $totalFee += $fees['fixed'];
        }
        
        if (isset($fees['percentage'])) {
            $totalFee += $amount * ($fees['percentage'] / 100);
        }
        
        return $totalFee;
    }
}
