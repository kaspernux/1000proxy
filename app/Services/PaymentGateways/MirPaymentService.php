<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MirPaymentService implements PaymentGatewayInterface
{
    private ?string $apiKey;
    private string $apiUrl;
    private ?string $merchantId;
    private bool $enabled = false;

    public function __construct()
    {
        $this->apiKey = config('services.mir.api_key');
        $this->apiUrl = config('services.mir.api_url', 'https://api.mir-pay.ru/v1');
        $this->merchantId = config('services.mir.merchant_id');
        $this->enabled = !empty($this->apiKey) && !empty($this->merchantId);
    }

    /**
     * Create a new payment
     */
    public function createPayment(array $paymentData): array
    {
        try {
            // Convert USD to RUB (you might want to use a real exchange rate API)
            $usdToRub = 90; // This should be dynamic from an exchange rate API
            $rubAmount = $paymentData['amount'] * $usdToRub;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/payments', [
                'merchant_id' => $this->merchantId,
                'amount' => round($rubAmount, 2),
                'currency' => 'RUB',
                'order_id' => $paymentData['order_id'],
                'description' => $paymentData['description'] ?? 'Proxy Service Order',
                'return_url' => $paymentData['success_url'] ?? url('/checkout/success'),
                'cancel_url' => $paymentData['cancel_url'] ?? url('/checkout/cancel'),
                'notification_url' => url('/webhooks/mir'),
                'customer' => [
                    'email' => $paymentData['customer_email'] ?? null,
                    'phone' => $paymentData['customer_phone'] ?? null,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('MIR payment created successfully', [
                    'payment_id' => $data['payment_id'] ?? null,
                    'order_id' => $paymentData['order_id']
                ]);

                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'payment_id' => $data['payment_id'],
                        'payment_url' => $data['payment_url'],
                        'amount' => $rubAmount,
                        'currency' => 'RUB',
                        'usd_amount' => $paymentData['amount'],
                        'exchange_rate' => $usdToRub,
                        'status' => $data['status'] ?? 'pending',
                        'expires_at' => $data['expires_at'] ?? null,
                    ]
                ];
            }

            Log::error('MIR payment creation failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'order_id' => $paymentData['order_id']
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create MIR payment: ' . $response->body(),
                'data' => []
            ];

        } catch (Exception $e) {
            Log::error('MIR payment creation exception', [
                'error' => $e->getMessage(),
                'order_id' => $paymentData['order_id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->apiUrl . '/payments/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'status' => $data['status'],
                        'amount' => $data['amount'],
                        'currency' => $data['currency'],
                        'order_id' => $data['order_id'] ?? null,
                        'paid_at' => $data['paid_at'] ?? null,
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment verification failed: ' . $response->body(),
                'data' => []
            ];

        } catch (Exception $e) {
            Log::error('MIR payment verification exception', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Process webhook from payment gateway
     */
    public function processWebhook(array $webhookData): array
    {
        try {
            $paymentStatus = $webhookData['status'] ?? null;
            $paymentId = $webhookData['payment_id'] ?? null;
            $orderId = $webhookData['order_id'] ?? null;

            if (!$paymentId || !$paymentStatus || !$orderId) {
                return [
                    'success' => false,
                    'error' => 'Missing required webhook data',
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'status' => $paymentStatus,
                    'payment_id' => $paymentId,
                    'order_id' => $orderId
                ]
            ];

        } catch (Exception $e) {
            Log::error('MIR webhook processing exception', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'currencies' => [
                    'rub' => 'Russian Ruble'
                ]
            ]
        ];
    }

    /**
     * Get supported payment methods
     */
    public function getPaymentMethods(): array
    {
        return [
            'mir' => [
                'name' => 'MIR Payment System',
                'description' => 'Pay in Russian Rubles using MIR cards',
                'supported_currencies' => ['RUB']
            ]
        ];
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/payments/' . $paymentId . '/refund', [
                'amount' => $amount,
                'reason' => 'Customer refund request'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'refund_id' => $data['refund_id'],
                        'amount' => $data['amount'],
                        'status' => $data['status']
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Refund failed: ' . $response->body(),
                'data' => []
            ];

        } catch (Exception $e) {
            Log::error('MIR refund exception', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get gateway information
     */
    public function getGatewayInfo(): array
    {
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'id' => 'mir',
                'name' => 'MIR Payment System',
                'type' => 'card_payment',
                'supports_refunds' => true,
                'supports_webhooks' => true,
                'requires_kyc' => false,
                'processing_time' => 'Instant',
                'supported_currencies' => ['RUB'],
                'supported_countries' => ['RU'],
                'enabled' => $this->enabled,
                'missing_configuration' => $this->enabled ? [] : array_values(array_filter([
                    empty($this->apiKey) ? 'api_key' : null,
                    empty($this->merchantId) ? 'merchant_id' : null,
                ])),
            ]
        ];
    }
}
