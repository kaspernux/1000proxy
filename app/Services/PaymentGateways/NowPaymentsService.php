<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class NowPaymentsService implements PaymentGatewayInterface
{
    /**
     * Determine base API URL based on environment config
     */
    private function getBaseUrl(): string
    {
        // Prefer dedicated nowpayments.php config (published by package) for env switching
        $env = config('nowpayments.env', 'sandbox');
        $live = config('nowpayments.liveUrl', 'https://api.nowpayments.io/v1');
        $sandbox = config('nowpayments.sandboxUrl', 'https://api-sandbox.nowpayments.io/v1');
        return $env === 'live' ? $live : $sandbox;
    }

    /**
     * Estimate the crypto amount for a given fiat amount & currency pair.
     * Standardized response: { success, error, data: { amount_from, currency_from, amount_to, currency_to } }
     */
    public function estimatePrice(float $amount, string $currencyFrom, string $currencyTo): array
    {
        try {
            $apiKey = config('services.nowpayments.key');
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'error' => 'NowPayments API key not configured',
                    'data' => []
                ];
            }

            $base = rtrim($this->getBaseUrl(), '/');
            $performRequest = function(string $baseUrl) use ($apiKey, $amount, $currencyFrom, $currencyTo) {
                return \Illuminate\Support\Facades\Http::withHeaders([
                    'x-api-key' => $apiKey,
                ])->get($baseUrl . '/estimate', [
                    'amount' => $amount,
                    'currency_from' => strtolower($currencyFrom),
                    'currency_to' => strtolower($currencyTo),
                ]);
            };

            $response = $performRequest($base);
            $attemptedFallback = false;

            // If env not explicitly set and we got a 401/403, auto-try alternate environment
            if (in_array($response->status(), [401,403]) && empty(env('NOWPAYMENTS_ENV'))) {
                $attemptedFallback = true;
                $alt = str_contains($base, 'sandbox')
                    ? rtrim(config('nowpayments.liveUrl', 'https://api.nowpayments.io/v1'), '/')
                    : rtrim(config('nowpayments.sandboxUrl', 'https://api-sandbox.nowpayments.io/v1'), '/');
                \Log::info('NowPayments estimate retrying with alternate environment', [
                    'original_base' => $base,
                    'alternate_base' => $alt,
                ]);
                $response = $performRequest($alt);
                $base = $alt; // For downstream logging context
            }

            $json = null;
            $isSuccess = $response->successful();
            if ($response->header('content-type') && str_contains($response->header('content-type'), 'application/json')) {
                $json = $response->json();
            } else {
                // Attempt decode
                $decoded = json_decode($response->body(), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $json = $decoded;
                }
            }
            if ($isSuccess) {
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'amount_from' => $json['amount_from'] ?? $amount,
                        'currency_from' => $json['currency_from'] ?? strtolower($currencyFrom),
                        'amount_to' => $json['amount_to'] ?? null,
                        'currency_to' => $json['currency_to'] ?? strtolower($currencyTo),
                    ]
                ];
            }
            $apiError = null;
            if (is_array($json)) {
                $apiError = $json['message'] ?? ($json['error'] ?? null);
                $errorCode = $json['code'] ?? null;
            }
            Log::warning('NowPayments estimate failed', [
                'status' => $response->status(),
                'endpoint' => $base . '/estimate',
                'params' => [
                    'amount' => $amount,
                    'currency_from' => strtolower($currencyFrom),
                    'currency_to' => strtolower($currencyTo),
                ],
                'api_error' => $apiError ?? null,
                'api_code' => $errorCode ?? null,
                'body' => $response->body(),
                'attempted_fallback' => $attemptedFallback,
            ]);
            $friendly = match ($response->status()) {
                401, 403 => 'Estimate rejected (API key / environment mismatch)',
                429 => 'Rate limited by NowPayments',
                400 => 'Invalid currency pair or amount',
                default => 'Failed to fetch estimate: HTTP ' . $response->status(),
            };
            if (!empty($apiError)) {
                $friendly .= ' - ' . $apiError;
            }
            return [
                'success' => false,
                'error' => $friendly,
                'data' => [
                    'raw_status' => $response->status(),
                    'raw_error' => $apiError,
                ]
            ];
        } catch (Exception $e) {
            Log::error('NowPayments estimate exception', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Retrieve minimum payment amount for a fiat/crypto pair.
     */
    public function getMinimumAmount(string $currencyFrom, string $currencyTo): array
    {
        try {
            $apiKey = config('services.nowpayments.key');
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'error' => 'NowPayments API key not configured',
                    'data' => []
                ];
            }
            $base = rtrim($this->getBaseUrl(), '/');
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey,
            ])->get($base . '/min-amount', [
                'currency_from' => strtolower($currencyFrom),
                'currency_to' => strtolower($currencyTo),
            ]);
            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'min_amount' => $json['min_amount'] ?? null,
                        'currency_from' => $json['currency_from'] ?? strtolower($currencyFrom),
                        'currency_to' => $json['currency_to'] ?? strtolower($currencyTo),
                    ]
                ];
            }
            Log::warning('NowPayments min-amount failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'success' => false,
                'error' => 'Failed to fetch minimum amount: ' . $response->status(),
                'data' => []
            ];
        } catch (Exception $e) {
            Log::error('NowPayments min-amount exception', [ 'error' => $e->getMessage() ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Create a new payment
     */
    public function createPayment(array $paymentData): array
    {
        try {
            $apiKey = config('services.nowpayments.key');
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'error' => 'NowPayments API key not configured',
                    'data' => []
                ];
            }
            if (!isset($paymentData['amount'])) {
                return [ 'success' => false, 'error' => 'Missing required payment amount', 'data' => [] ];
            }
            // Allow wallet top-ups without a persisted order by synthesizing an order_id
            if (empty($paymentData['order_id'])) {
                $paymentData['order_id'] = 'WTU-' . now()->format('YmdHis') . '-' . substr(md5(uniqid('', true)),0,6);
            }

            $priceCurrency = strtoupper($paymentData['currency'] ?? 'USD');
            $payCurrency = strtolower($paymentData['crypto_currency'] ?? $paymentData['pay_currency'] ?? '');

            $payload = [
                'price_amount' => (float)$paymentData['amount'],
                'price_currency' => $priceCurrency,
                'order_id' => (string)$paymentData['order_id'],
                'order_description' => $paymentData['description'] ?? 'Proxy Service Order',
                'ipn_callback_url' => config('nowpayments.callbackUrl') ?: route('webhook.nowpay'),
                'success_url' => $paymentData['success_url'] ?? (rtrim(config('app.url'), '/') . (env('NOWPAYMENTS_SUCCESS_URL','/checkout/success'))),
                'cancel_url' => $paymentData['cancel_url'] ?? (rtrim(config('app.url'), '/') . (env('NOWPAYMENTS_CANCEL_URL','/checkout/cancel'))),
            ];
            // Forward optional metadata (e.g. transaction_id, wallet_topup) so NowPayments will include it in IPN
            if (!empty($paymentData['metadata']) && is_array($paymentData['metadata'])) {
                $payload['metadata'] = $paymentData['metadata'];
            }
            // Only include pay_currency if explicitly provided; omitting lets user select coin on NP page and avoids min-amount errors
            if (!empty($payCurrency)) {
                $payload['pay_currency'] = $payCurrency;
            }

            $base = rtrim($this->getBaseUrl(), '/');
            // Use invoice endpoint to obtain redirectable invoice_url
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Accept' => 'application/json',
            ])->post($base . '/invoice', $payload);
            $invoice = $response->json();

            if ($response->successful() && isset($invoice['invoice_url'])) {
                $paymentId = $invoice['invoice_id'] ?? ($invoice['id'] ?? null);
                Log::info('NowPayments invoice created successfully', [
                    'invoice_id' => $paymentId,
                    'order_id' => $paymentData['order_id'],
                    'env' => config('nowpayments.env')
                ]);
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'payment_id' => $paymentId,
                        'order_id' => (string)($paymentData['order_id'] ?? ''),
                        'payment_url' => $invoice['invoice_url'],
                        'amount' => $invoice['price_amount'] ?? $payload['price_amount'],
                        'currency' => $invoice['price_currency'] ?? $payload['price_currency'],
                        'crypto_currency' => $invoice['pay_currency'] ?? $payload['pay_currency'],
                        'crypto_amount' => $invoice['pay_amount'] ?? null,
                        'status' => $invoice['payment_status'] ?? 'waiting',
                        'payout_currency' => $invoice['payout_currency']
                            ?? $invoice['outcome_currency']
                            ?? ($invoice['pay_currency'] ?? $invoice['price_currency'] ?? null),
                    ]
                ];
            }

            // If we attempted with a specific coin and NP says it's under minimal, retry without forcing coin
            $errMsg = is_array($invoice) ? ($invoice['message'] ?? ($invoice['error'] ?? '')) : '';
            $underMin = (stripos($errMsg, 'less than minimal') !== false) || (stripos($errMsg, 'minimum') !== false);
            $retried = false;
            if (!empty($payCurrency) && $underMin) {
                $retryPayload = $payload; unset($retryPayload['pay_currency']);
                $retried = true;
                $retryResp = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'Accept' => 'application/json',
                ])->post($base . '/invoice', $retryPayload);
                $retryJson = $retryResp->json();
                if ($retryResp->successful() && isset($retryJson['invoice_url'])) {
                    $paymentId = $retryJson['invoice_id'] ?? ($retryJson['id'] ?? null);
                    Log::info('NowPayments invoice created after removing pay_currency (min fallback)', [
                        'invoice_id' => $paymentId,
                        'order_id' => $paymentData['order_id'],
                        'requested_pay_currency' => $payCurrency,
                    ]);
                    return [
                        'success' => true,
                        'error' => null,
                        'data' => [
                            'payment_id' => $paymentId,
                            'order_id' => (string)($paymentData['order_id'] ?? ''),
                            'payment_url' => $retryJson['invoice_url'],
                            'amount' => $retryJson['price_amount'] ?? $payload['price_amount'],
                            'currency' => $retryJson['price_currency'] ?? $payload['price_currency'],
                            // When coin selection is open, NP doesn't lock a crypto coin yet
                            'crypto_currency' => $retryJson['pay_currency'] ?? null,
                            'crypto_amount' => $retryJson['pay_amount'] ?? null,
                            'status' => $retryJson['payment_status'] ?? 'waiting',
                            'payout_currency' => $retryJson['payout_currency']
                                ?? $retryJson['outcome_currency']
                                ?? ($retryJson['pay_currency'] ?? $retryJson['price_currency'] ?? null),
                        ]
                    ];
                }
            }

            Log::error('NowPayments invoice creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $paymentData['order_id'],
                'env' => config('nowpayments.env'),
                'under_minimum' => $underMin,
                'retried_without_coin' => $retried,
            ]);
            $msg = $invoice['message'] ?? ($invoice['error'] ?? ('Failed to create invoice: HTTP '.$response->status()));
            return [ 'success' => false, 'error' => $msg, 'data' => [] ];

        } catch (Exception $e) {
            Log::error('NowPayments payment creation exception', [
                'order_id' => $paymentData['order_id'] ?? null,
                'error' => $e->getMessage(),
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
            $apiKey = config('services.nowpayments.key');
            if (empty($apiKey)) {
                return [ 'success' => false, 'error' => 'NowPayments API key not configured', 'data' => [] ];
            }
            $base = rtrim($this->getBaseUrl(), '/');
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Accept' => 'application/json',
            ])->get($base.'/payment/'.$paymentId);
            $json = $response->json();
            if ($response->successful() && is_array($json)) {
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'status' => $json['payment_status'] ?? 'unknown',
                        'amount' => $json['price_amount'] ?? null,
                        'currency' => $json['price_currency'] ?? null,
                        'crypto_amount' => $json['pay_amount'] ?? null,
                        'crypto_currency' => $json['pay_currency'] ?? null,
                        'payout_currency' => $json['payout_currency']
                            ?? $json['outcome_currency']
                            ?? ($json['pay_currency'] ?? $json['price_currency'] ?? null),
                        'order_id' => $json['order_id'] ?? null,
                    ]
                ];
            }
            Log::warning('NowPayments verify failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payment_id' => $paymentId,
            ]);
            return [ 'success' => false, 'error' => 'Verification failed: HTTP '.$response->status(), 'data' => [] ];
        } catch (Exception $e) {
            Log::error('NowPayments payment verification exception', [ 'error' => $e->getMessage(), 'payment_id' => $paymentId ]);
            return [ 'success' => false, 'error' => $e->getMessage(), 'data' => [] ];
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
                    'error' => 'Missing required webhook data',
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'status' => $paymentStatus,
                    'order_id' => $orderId
                ]
            ];

        } catch (Exception $e) {
            Log::error('NowPayments webhook processing exception', [
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
        try {
            $apiKey = config('services.nowpayments.key');
            if (empty($apiKey)) {
                return [ 'success' => false, 'error' => 'NowPayments API key not configured', 'data' => ['currencies' => []] ];
            }
            $base = rtrim($this->getBaseUrl(), '/');
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Accept' => 'application/json'
            ])->get($base.'/currencies');
            if ($response->successful()) {
                $json = $response->json();
                // API returns an array; normalize to associative symbol=>symbol
                $list = [];
                if (is_array($json)) {
                    foreach ($json as $code) {
                        if (is_string($code)) { $list[strtolower($code)] = strtoupper($code); }
                    }
                }
                return [ 'success' => true, 'error' => null, 'data' => [ 'currencies' => $list ] ];
            }
            Log::warning('NowPayments currencies fetch failed', [ 'status' => $response->status(), 'body' => $response->body() ]);

        } catch (Exception $e) {
            Log::error('NowPayments get currencies exception', [
                'error' => $e->getMessage()
            ]);

            // Return default supported cryptocurrencies
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'currencies' => [
                        'btc' => 'BTC',
                        'eth' => 'ETH',
                        'xmr' => 'XMR',
                        'ltc' => 'LTC',
                        'doge' => 'DOGE',
                        'ada' => 'ADA',
                        'dot' => 'DOT',
                        'sol' => 'SOL',
                    ]
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
                'supported_currencies' => $this->getSupportedCurrencies()['data']['currencies'] ?? []
            ]
        ];
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array
    {
        // NowPayments doesn't typically support automatic refunds for crypto;
        // these are usually handled manually. We still return a standardized
        // structure so upstream code can rely on { success, error, data }.
        return [
            'success' => false,
            'error' => 'Cryptocurrency refunds must be processed manually',
            'data' => [
                'payment_id' => $paymentId,
                'requested_amount' => $amount,
                'refundable' => false,
            ]
        ];
    }

    /**
     * Get gateway information
     */
    public function getGatewayInfo(): array
    {
        $currenciesResponse = $this->getSupportedCurrencies();
        $currencies = $currenciesResponse['data']['currencies'] ?? [];

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'id' => 'nowpayments',
                'name' => 'NowPayments',
                'type' => 'cryptocurrency',
                'supports_refunds' => false,
                'supports_webhooks' => true,
                'requires_kyc' => false,
                'processing_time' => 'Instant to 60 minutes',
                'supported_currencies' => $currencies,
            ]
        ];
    }
}
