<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalPaymentService implements PaymentGatewayInterface
{
    private ?string $clientId;
    private ?string $clientSecret;
    private string $apiUrl;
    private ?string $accessToken;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->apiUrl = config('services.paypal.sandbox') ?
            'https://api.sandbox.paypal.com' :
            'https://api.paypal.com';
        $this->accessToken = $this->getAccessToken();
    }

    private function getAccessToken(): ?string
    {
        if (!$this->clientId || !$this->clientSecret) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->apiUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'];
            }

            Log::error('PayPal access token error: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('PayPal access token exception: ' . $e->getMessage());
            return null;
        }
    }

    public function createPayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $paymentData['currency'] ?? 'USD',
                            'value' => number_format($paymentData['amount'], 2, '.', '')
                        ],
                        'reference_id' => $paymentData['order_id'] ?? null,
                        'description' => $paymentData['description'] ?? 'Proxy Service Payment'
                    ]
                ],
                'application_context' => [
                    'return_url' => config('app.url') . '/payment/paypal/success',
                    'cancel_url' => config('app.url') . '/payment/paypal/cancel',
                    'brand_name' => config('app.name'),
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW'
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $approvalUrl = collect($data['links'])->firstWhere('rel', 'approve')['href'] ?? null;

                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'approval_url' => $approvalUrl,
                    'status' => $data['status'],
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'] ?? 'USD'
                ];
            }

            Log::error('PayPal payment creation failed: ' . $response->body());

            return [
                'success' => false,
                'error' => 'Payment creation failed',
                'details' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('PayPal payment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Payment processing error',
                'details' => $e->getMessage()
            ];
        }
    }

    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken
            ])->get($this->apiUrl . '/v2/checkout/orders/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'status' => $data['status'],
                    'amount' => $data['purchase_units'][0]['amount']['value'] ?? 0,
                    'currency' => $data['purchase_units'][0]['amount']['currency_code'] ?? 'USD',
                    'verified' => $data['status'] === 'COMPLETED'
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment verification failed'
            ];

        } catch (\Exception $e) {
            Log::error('PayPal verification error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Verification error',
                'details' => $e->getMessage()
            ];
        }
    }

    public function capturePayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/v2/checkout/orders/' . $paymentId . '/capture');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'status' => $data['status'],
                    'capture_id' => $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
                    'amount' => $data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment capture failed',
                'details' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('PayPal capture error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Capture processing error',
                'details' => $e->getMessage()
            ];
        }
    }

    public function processWebhook(array $webhookData): array
    {
        try {
            $event = $webhookData['event_type'] ?? null;
            $resource = $webhookData['resource'] ?? [];

            switch ($event) {
                case 'CHECKOUT.ORDER.APPROVED':
                    return $this->handleOrderApproved($resource);

                case 'PAYMENT.CAPTURE.COMPLETED':
                    return $this->handlePaymentCompleted($resource);

                case 'PAYMENT.CAPTURE.DENIED':
                    return $this->handlePaymentDenied($resource);

                case 'CUSTOMER.DISPUTE.CREATED':
                    return $this->handleDispute($resource);

                default:
                    return [
                        'success' => true,
                        'message' => 'Event processed but no action taken'
                    ];
            }

        } catch (\Exception $e) {
            Log::error('PayPal webhook error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Webhook processing error'
            ];
        }
    }

    private function handleOrderApproved(array $resource): array
    {
        Log::info('PayPal order approved: ' . $resource['id']);

        return [
            'success' => true,
            'action' => 'order_approved',
            'payment_id' => $resource['id']
        ];
    }

    private function handlePaymentCompleted(array $resource): array
    {
        Log::info('PayPal payment completed: ' . $resource['id']);

        return [
            'success' => true,
            'action' => 'payment_completed',
            'payment_id' => $resource['id'],
            'amount' => $resource['amount']['value'] ?? 0
        ];
    }

    private function handlePaymentDenied(array $resource): array
    {
        Log::warning('PayPal payment denied: ' . $resource['id']);

        return [
            'success' => true,
            'action' => 'payment_denied',
            'payment_id' => $resource['id'],
            'reason' => $resource['status_details']['reason'] ?? 'Payment denied'
        ];
    }

    private function handleDispute(array $resource): array
    {
        Log::warning('PayPal dispute created: ' . $resource['dispute_id']);

        return [
            'success' => true,
            'action' => 'dispute_created',
            'dispute_id' => $resource['dispute_id'],
            'amount' => $resource['disputed_transactions'][0]['gross_amount']['value'] ?? 0
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'INR', 'ILS',
            'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB',
            'SGD', 'SEK', 'CHF', 'THB', 'USD'
        ];
    }

    public function getPaymentMethods(): array
    {
        return [
            'paypal' => [
                'name' => 'PayPal',
                'types' => ['paypal_account']
            ],
            'card' => [
                'name' => 'Credit/Debit Card',
                'types' => ['visa', 'mastercard', 'amex', 'discover']
            ],
            'bancontact' => [
                'name' => 'Bancontact',
                'types' => ['bancontact']
            ],
            'blik' => [
                'name' => 'BLIK',
                'types' => ['blik']
            ],
            'eps' => [
                'name' => 'eps',
                'types' => ['eps']
            ],
            'giropay' => [
                'name' => 'giropay',
                'types' => ['giropay']
            ],
            'ideal' => [
                'name' => 'iDEAL',
                'types' => ['ideal']
            ],
            'mybank' => [
                'name' => 'MyBank',
                'types' => ['mybank']
            ],
            'p24' => [
                'name' => 'Przelewy24',
                'types' => ['p24']
            ],
            'sepa' => [
                'name' => 'SEPA',
                'types' => ['sepa']
            ],
            'sofort' => [
                'name' => 'Sofort',
                'types' => ['sofort']
            ],
            'venmo' => [
                'name' => 'Venmo',
                'types' => ['venmo']
            ]
        ];
    }

    public function refundPayment(string $paymentId, float $amount = null): array
    {
        try {
            // First, get the capture ID from the order
            $orderResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken
            ])->get($this->apiUrl . '/v2/checkout/orders/' . $paymentId);

            if (!$orderResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Could not retrieve order details'
                ];
            }

            $orderData = $orderResponse->json();
            $captureId = $orderData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            if (!$captureId) {
                return [
                    'success' => false,
                    'error' => 'No capture found for this payment'
                ];
            }

            $refundData = [];

            if ($amount !== null) {
                $refundData['amount'] = [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => $orderData['purchase_units'][0]['amount']['currency_code'] ?? 'USD'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/v2/payments/captures/' . $captureId . '/refund', $refundData);

            if ($response->successful()) {
                $refundResponse = $response->json();

                return [
                    'success' => true,
                    'refund_id' => $refundResponse['id'],
                    'amount' => $refundResponse['amount']['value'] ?? $amount,
                    'status' => $refundResponse['status']
                ];
            }

            return [
                'success' => false,
                'error' => 'Refund failed',
                'details' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('PayPal refund error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Refund processing error',
                'details' => $e->getMessage()
            ];
        }
    }

    public function getGatewayInfo(): array
    {
        return [
            'name' => 'PayPal',
            'type' => 'redirect',
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
            'settlement_time' => '1-3 business days'
        ];
    }

    public function isEnabled(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    public function supportsCurrency(string $currency): bool
    {
        $supportedCurrencies = $this->getSupportedCurrencies();
        return in_array(strtolower($currency), array_map('strtolower', $supportedCurrencies));
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function getIcon(): string
    {
        return 'paypal-icon.svg';
    }

    public function getDescription(): string
    {
        return 'Pay securely with your PayPal account';
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
