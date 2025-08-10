<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripePaymentService implements PaymentGatewayInterface
{
    private ?string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret_key');
        $this->apiUrl = 'https://api.stripe.com/v1';
    }

    public function createPayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post($this->apiUrl . '/payment_intents', [
                'amount' => $paymentData['amount'] * 100, // Convert to cents
                'currency' => $paymentData['currency'] ?? 'usd',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'order_id' => $paymentData['order_id'] ?? null,
                    'user_id' => $paymentData['user_id'] ?? null
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'payment_id' => $data['id'],
                        'client_secret' => $data['client_secret'],
                        'status' => $data['status'],
                        'amount' => $data['amount'] / 100,
                        'currency' => $data['currency']
                    ]
                ];
            }

            Log::error('Stripe payment creation failed: ' . $response->body());

            return [
                'success' => false,
                'error' => 'Payment creation failed',
                'data' => [
                    'details' => $response->json()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Stripe payment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Payment processing error',
                'data' => [
                    'details' => $e->getMessage()
                ]
            ];
        }
    }

    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->get($this->apiUrl . '/payment_intents/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'payment_id' => $data['id'],
                        'status' => $data['status'],
                        'amount' => $data['amount'] / 100,
                        'currency' => $data['currency'],
                        'verified' => $data['status'] === 'succeeded'
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment verification failed',
                'data' => []
            ];

        } catch (\Exception $e) {
            Log::error('Stripe verification error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Verification error',
                'data' => [
                    'details' => $e->getMessage()
                ]
            ];
        }
    }

    public function processWebhook(array $webhookData): array
    {
        try {
            $event = $webhookData['type'] ?? null;
            $data = $webhookData['data']['object'] ?? [];

            switch ($event) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentSuccess($data);

                case 'payment_intent.payment_failed':
                    return $this->handlePaymentFailed($data);

                case 'charge.dispute.created':
                    return $this->handleDispute($data);

                default:
                    return [
                        'success' => true,
                        'error' => null,
                        'data' => [
                            'message' => 'Event processed but no action taken'
                        ]
                    ];
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Webhook processing error',
                'data' => []
            ];
        }
    }

    private function handlePaymentSuccess(array $data): array
    {
        Log::info('Stripe payment succeeded: ' . $data['id']);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'action' => 'payment_confirmed',
                'payment_id' => $data['id'],
                'amount' => $data['amount'] / 100
            ]
        ];
    }

    private function handlePaymentFailed(array $data): array
    {
        Log::warning('Stripe payment failed: ' . $data['id']);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'action' => 'payment_failed',
                'payment_id' => $data['id'],
                'failure_message' => $data['last_payment_error']['message'] ?? 'Payment failed'
            ]
        ];
    }

    private function handleDispute(array $data): array
    {
        Log::warning('Stripe dispute created: ' . $data['id']);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'action' => 'dispute_created',
                'dispute_id' => $data['id'],
                'amount' => $data['amount'] / 100
            ]
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'usd', 'eur', 'gbp', 'cad', 'aud', 'jpy', 'chf', 'sek', 'nok', 'dkk',
            'pln', 'czk', 'huf', 'bgn', 'ron', 'hrk', 'try', 'ils', 'sgd', 'hkd',
            'inr', 'krw', 'myr', 'php', 'thb', 'vnd', 'brl', 'mxn', 'cop', 'pen',
            'clp', 'ars', 'uyu', 'zar', 'egp', 'mad', 'ngn', 'ghs', 'kes', 'tzs'
        ];
    }

    public function getPaymentMethods(): array
    {
        return [
            'card' => [
                'name' => 'Credit/Debit Card',
                'types' => ['visa', 'mastercard', 'amex', 'discover', 'diners', 'jcb']
            ],
            'bancontact' => [
                'name' => 'Bancontact',
                'types' => ['bancontact']
            ],
            'giropay' => [
                'name' => 'giropay',
                'types' => ['giropay']
            ],
            'ideal' => [
                'name' => 'iDEAL',
                'types' => ['ideal']
            ],
            'sepa_debit' => [
                'name' => 'SEPA Direct Debit',
                'types' => ['sepa_debit']
            ],
            'sofort' => [
                'name' => 'Sofort',
                'types' => ['sofort']
            ],
            'alipay' => [
                'name' => 'Alipay',
                'types' => ['alipay']
            ],
            'wechat_pay' => [
                'name' => 'WeChat Pay',
                'types' => ['wechat_pay']
            ]
        ];
    }

    public function refundPayment(string $paymentId, float $amount = null): array
    {
        try {
            $data = ['payment_intent' => $paymentId];

            if ($amount !== null) {
                $data['amount'] = $amount * 100; // Convert to cents
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post($this->apiUrl . '/refunds', $data);

            if ($response->successful()) {
                $refundData = $response->json();

                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'refund_id' => $refundData['id'],
                        'amount' => $refundData['amount'] / 100,
                        'status' => $refundData['status']
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Refund failed',
                'data' => [
                    'details' => $response->json()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Stripe refund error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Refund processing error',
                'data' => [
                    'details' => $e->getMessage()
                ]
            ];
        }
    }

    public function getGatewayInfo(): array
    {
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'id' => 'stripe',
                'name' => 'Stripe',
                'type' => 'card',
                'supports' => [
                    'payments' => true,
                    'refunds' => true,
                    'webhooks' => true,
                    'recurring' => true,
                    'multi_currency' => true
                ],
                'fees' => [
                    'percentage' => 2.9,
                    'fixed' => 0.30
                ],
                'processing_time' => 'instant',
                'settlement_time' => '2-7 business days'
            ]
        ];
    }

    public function isEnabled(): bool
    {
        return !empty($this->apiKey);
    }

    public function supportsCurrency(string $currency): bool
    {
        $supportedCurrencies = $this->getSupportedCurrencies();
        return in_array(strtolower($currency), array_map('strtolower', $supportedCurrencies));
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function getIcon(): string
    {
        return 'stripe-icon.svg';
    }

    public function getDescription(): string
    {
        return 'Pay securely with your credit or debit card';
    }

    public function getFees(): array
    {
        return [
            'percentage' => 2.9,
            'fixed' => 0.30,
            'currency' => 'USD'
        ];
    }

    public function getProcessingTime(): string
    {
        return 'Instant';
    }

    public function isInstant(): bool
    {
        return true;
    }
}
