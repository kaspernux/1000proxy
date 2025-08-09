<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class NowPaymentsService implements PaymentGatewayInterface
{
    /**
     * Create a new payment
     */
    public function createPayment(array $paymentData): array
    {
        try {
            $nowPayments = nowpayments();
            
            $payment = $nowPayments->createPayment([
                'price_amount' => $paymentData['amount'],
                'price_currency' => $paymentData['currency'] ?? 'USD',
                'pay_currency' => $paymentData['crypto_currency'] ?? 'btc',
                'order_id' => $paymentData['order_id'],
                'order_description' => $paymentData['description'] ?? 'Proxy Service Order',
                'ipn_callback_url' => route('webhook.nowpay'),
                'success_url' => $paymentData['success_url'] ?? url('/checkout/success'),
                'cancel_url' => $paymentData['cancel_url'] ?? url('/checkout/cancel'),
            ]);

            if ($payment && isset($payment['payment_id'])) {
                Log::info('NowPayments payment created successfully', [
                    'payment_id' => $payment['payment_id'],
                    'order_id' => $paymentData['order_id']
                ]);

                return [
                    'success' => true,
                    'payment_id' => $payment['payment_id'],
                    'payment_url' => $payment['invoice_url'] ?? null,
                    'amount' => $payment['price_amount'],
                    'currency' => $payment['price_currency'],
                    'crypto_currency' => $payment['pay_currency'],
                    'crypto_amount' => $payment['pay_amount'] ?? null,
                    'status' => $payment['payment_status'] ?? 'waiting',
                    'expires_at' => $payment['created_at'] ?? null,
                ];
            }

            Log::error('NowPayments payment creation failed', [
                'response' => $payment,
                'order_id' => $paymentData['order_id'],
                'error' => $payment['message'] ?? null,
            ]);

            return [
                'success' => false,
                'error' => $payment['message'] ?? 'Failed to create payment',
            ];

        } catch (Exception $e) {
            Log::error('NowPayments payment creation exception', [
                'error' => $e->getMessage(),
                'order_id' => $paymentData['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $nowPayments = nowpayments();
            $payment = $nowPayments->getPaymentStatus($paymentId);

            if ($payment) {
                return [
                    'success' => true,
                    'status' => $payment['payment_status'],
                    'amount' => $payment['price_amount'],
                    'currency' => $payment['price_currency'],
                    'crypto_amount' => $payment['pay_amount'] ?? null,
                    'crypto_currency' => $payment['pay_currency'] ?? null,
                    'order_id' => $payment['order_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment not found'
            ];

        } catch (Exception $e) {
            Log::error('NowPayments payment verification exception', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process webhook from payment gateway
     */
    public function processWebhook(array $webhookData): array
    {
        try {
            $paymentStatus = $webhookData['payment_status'] ?? null;
            $orderId = $webhookData['order_id'] ?? null;

            if (!$orderId || !$paymentStatus) {
                return [
                    'success' => false,
                    'error' => 'Missing required webhook data'
                ];
            }

            // The webhook controller already handles the processing
            // This method is for interface compliance
            return [
                'success' => true,
                'status' => $paymentStatus,
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            Log::error('NowPayments webhook processing exception', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        try {
            $nowPayments = nowpayments();
            $currencies = $nowPayments->getCurrencies();

            return [
                'success' => true,
                'currencies' => $currencies ?? []
            ];

        } catch (Exception $e) {
            Log::error('NowPayments get currencies exception', [
                'error' => $e->getMessage()
            ]);

            // Return default supported cryptocurrencies
            return [
                'success' => true,
                'currencies' => [
                    'btc' => 'Bitcoin',
                    'eth' => 'Ethereum',
                    'xmr' => 'Monero',
                    'ltc' => 'Litecoin',
                    'doge' => 'Dogecoin',
                    'ada' => 'Cardano',
                    'dot' => 'Polkadot',
                    'sol' => 'Solana',
                ]
            ];
        }
    }

    /**
     * Get supported payment methods
     */
    public function getPaymentMethods(): array
    {
        return [
            'crypto' => [
                'name' => 'Cryptocurrency',
                'description' => 'Pay with various cryptocurrencies',
                'supported_currencies' => $this->getSupportedCurrencies()['currencies'] ?? []
            ]
        ];
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array
    {
        // NowPayments doesn't typically support automatic refunds for crypto
        // This would need to be handled manually
        return [
            'success' => false,
            'error' => 'Cryptocurrency refunds must be processed manually'
        ];
    }

    /**
     * Get gateway information
     */
    public function getGatewayInfo(): array
    {
        return [
            'name' => 'NowPayments',
            'type' => 'cryptocurrency',
            'supports_refunds' => false,
            'supports_webhooks' => true,
            'requires_kyc' => false,
            'processing_time' => 'Instant to 60 minutes',
            'supported_currencies' => $this->getSupportedCurrencies()['currencies'] ?? []
        ];
    }
}
