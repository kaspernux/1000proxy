<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Order;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use App\Livewire\Traits\LivewireAlertV4;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentProcessor extends Component
{
    use LivewireAlertV4;

    #[Reactive]
    public $order;

    public $customer;

    public $selectedGateway = 'wallet';
    public $paymentAmount = 0;
    public $processingPayment = false;
    public $paymentStep = 'select_gateway'; // select_gateway, processing, completed, failed

    // Payment form data
    public $cardNumber = '';
    public $expiryMonth = '';
    public $expiryYear = '';
    public $cvc = '';
    public $cardholderName = '';

    // Crypto payment data
    public $selectedCrypto = 'BTC';
    public $cryptoAmount = 0;
    public $cryptoAddress = '';
    public $paymentId = '';

    // PayPal data
    public $paypalOrderId = '';

    // Wallet data
    public $walletBalance = 0;
    public $walletSufficient = false;

    protected $listeners = [
        'paymentCompleted' => 'handlePaymentCompleted',
        'paymentFailed' => 'handlePaymentFailed'
    ];

    protected $rules = [
        'cardNumber' => 'required_if:selectedGateway,stripe|string|min:16|max:19',
        'expiryMonth' => 'required_if:selectedGateway,stripe|integer|min:1|max:12',
        'expiryYear' => 'required_if:selectedGateway,stripe|integer|min:2024|max:2034',
        'cvc' => 'required_if:selectedGateway,stripe|string|min:3|max:4',
        'cardholderName' => 'required_if:selectedGateway,stripe|string|min:2|max:100',
    ];

    // Accept query params via mount for Livewire route
    public $type = 'fiat';
    public $amount = 0;
    public $currency = 'USD';
    public $order_id = null;
    public $isWalletTopup = false;
    public $selectedCurrency = 'USD';

    public function mount($type = 'fiat', $amount = 0, $currency = 'USD', $order_id = null)
    {
        // Retrieve query params from request if available
        $request = request();
        $this->type = $request->input('type', $type);
        $this->amount = $request->input('amount', $amount);
        $this->currency = $request->input('currency', $currency);
        $this->order_id = $request->input('order_id', $order_id);
        $this->selectedGateway = $request->input('selectedGateway', $this->selectedGateway);

        // Optionally fetch order if order_id is present
        if ($this->order_id) {
            $this->order = \App\Models\Order::find($this->order_id);
            $this->paymentAmount = $this->order ? $this->order->total_amount : $this->amount;
        } else {
            $this->paymentAmount = $this->amount;
        }

        if (!$this->customer) {
            $this->customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
        }
        $this->loadWalletBalance();
        // Determine wallet topup context
        $this->isWalletTopup = ($this->type === 'wallet_topup');
        $this->selectedCurrency = $this->currency; // initialize

        // For wallet top-up: ensure selectedGateway is a valid non-wallet active gateway
        if ($this->isWalletTopup) {
            $activeTopup = $this->getActiveTopupGateways();
            // If URL param selectedGateway is inactive, reset
            if (empty($activeTopup)) {
                // No active external methods -> stay on placeholder and mark failed state earlier in UI
                $this->selectedGateway = null;
            } elseif ($this->selectedGateway === 'wallet' || !$this->selectedGateway || !in_array($this->selectedGateway, $activeTopup, true)) {
                $this->selectedGateway = $activeTopup[0];
            }
        }
    }

    public function render()
    {
        return view('livewire.components.payment-processor', [
            'availableGateways' => $this->getAvailableGateways(),
            'cryptoCurrencies' => $this->getCryptoCurrencies(),
            'paymentProgress' => $this->getPaymentProgress(),
            'activeTopupGateways' => $this->isWalletTopup ? $this->getActiveTopupGateways() : []
        ]);
    }
    /**
     * Fetch available gateways from backend and handle errors gracefully
     */

    public function selectGateway($gateway)
    {
        $this->selectedGateway = $gateway;
        $this->paymentStep = 'select_gateway';

        // Reset form data when switching gateways
        $this->resetPaymentData();

        // Load specific gateway data
        switch ($gateway) {
            case 'wallet':
                $this->loadWalletBalance();
                break;
            case 'nowpayments':
                $this->refreshCryptoEstimate();
                break;
        }
        $this->dispatch('updateUrlParam', params: ['selectedGateway' => $this->selectedGateway]);
    }

    public function selectCurrency($currency)
    {
        $this->selectedCurrency = $currency;
        $this->currency = $currency; // unify
        if ($this->selectedGateway === 'nowpayments') {
            $this->refreshCryptoEstimate();
        }
        $this->dispatch('updateUrlParam', params: ['currency' => $this->selectedCurrency]);
    }

    public function processPayment()
    {
        $this->validate();
        $this->processingPayment = true;
        $this->paymentStep = 'processing';
        // Route wallet top-up through dedicated flow for strict separation
        if ($this->isWalletTopup) {
            $this->processWalletTopup();
            return;
        }
        try {
            // For nowpayments, use selectedCrypto for crypto, else use selected currency for fiat
            // For NowPayments, price currency remains fiat (selectedCurrency) and crypto currency is selectedCrypto
            $currency = $this->selectedGateway === 'nowpayments' ? $this->selectedCurrency : $this->currency;
            // Use direct service to avoid cross-request auth issues
            $service = null;
            switch ($this->selectedGateway) {
                case 'stripe':
                    $service = app(\App\Services\PaymentGateways\StripePaymentService::class); break;
                case 'paypal':
                    $service = app(\App\Services\PaymentGateways\PayPalPaymentService::class); break;
                case 'mir':
                    $service = app(\App\Services\PaymentGateways\MirPaymentService::class); break;
                case 'nowpayments':
                    $service = app(\App\Services\PaymentGateways\NowPaymentsService::class); break;
                case 'wallet':
                    // Direct wallet debit
                    if ($this->walletSufficient) {
                        $this->handlePaymentCompleted([
                            'status' => 'completed',
                            'payment_method' => 'wallet'
                        ]);
                        return;
                    }
                    $this->handlePaymentFailed(['error' => 'Insufficient wallet balance']);
                    return;
            }
            if (!$service) {
                $this->handlePaymentFailed(['error' => 'Unsupported gateway']);
                return;
            }
            $payload = [
                'amount' => $this->paymentAmount,
                'currency' => $currency,
                'order_id' => $this->order->id ?? null,
                'description' => $this->isWalletTopup ? 'Wallet Top-up' : 'Order Payment',
                'crypto_currency' => $this->selectedGateway === 'nowpayments' ? strtolower($this->selectedCrypto) : null,
            ];
            if ($this->isWalletTopup && empty($payload['order_id'])) {
                $payload['order_id'] = 'WTU-' . now()->format('YmdHis') . '-' . substr(md5(uniqid('', true)),0,6);
            }
            $result = $service->createPayment($payload);
            if (isset($result['success']) && $result['success']) {
                $paymentPayload = $result['data'] ?? [];
                // For NowPayments we must redirect user to hosted invoice instead of marking as completed immediately
                if ($this->selectedGateway === 'nowpayments') {
                    if (!empty($paymentPayload['payment_url'])) {
                        // Persist invoice URL on order (if applicable) for later viewing
                        if ($this->order) {
                            try { $this->order->markAsProcessing($paymentPayload['payment_url']); } catch (\Throwable $e) { \Log::warning('Failed to mark order processing with invoice URL', ['error'=>$e->getMessage()]); }
                        }
                        $this->alert('info', 'Redirecting to crypto invoice...');
                        $this->dispatch('redirectAfterPayment', url: $paymentPayload['payment_url'], delay: 400);
                        // Fallback server-side redirect in case JS event not handled
                        return redirect()->away($paymentPayload['payment_url']);
                        // Leave step as 'processing'; webhook will emit paymentCompleted/paymentFailed events
                    }
                    // If no payment_url returned treat as failure
                    $this->handlePaymentFailed(['error' => 'Failed to obtain NowPayments invoice URL']);
                    return;
                }
                // Non-crypto gateways: mark completed immediately; crypto handled by webhook
                $this->handlePaymentCompleted($paymentPayload);
            } else {
                $this->handlePaymentFailed(['error' => $result['error'] ?? 'Unknown error']);
            }
        } catch (\Exception $e) {
            $this->handlePaymentFailed(['error' => $e->getMessage()]);
        }
    }

    public function topUpWallet()
    {
        $this->processWalletTopup();
    }

    private function processWalletTopup()
    {
        if ($this->selectedGateway === 'wallet') {
            $this->handlePaymentFailed(['error' => 'Select an external gateway to top up your wallet.']);
            return;
        }
        try {
            $customer = Auth::guard('customer')->user();
            if (!$customer) {
                $this->handlePaymentFailed(['error' => 'Not authenticated']);
                return;
            }
            $gateway = $this->selectedGateway === 'nowpayments' ? 'nowpayments' : $this->selectedGateway;
            switch ($gateway) {
                case 'stripe':
                    $service = app(\App\Services\PaymentGateways\StripePaymentService::class); break;
                case 'paypal':
                    $service = app(\App\Services\PaymentGateways\PayPalPaymentService::class); break;
                case 'mir':
                    $service = app(\App\Services\PaymentGateways\MirPaymentService::class); break;
                case 'nowpayments':
                    $service = app(\App\Services\PaymentGateways\NowPaymentsService::class); break;
                default:
                    $this->handlePaymentFailed(['error' => 'Unsupported gateway']); return;
            }
            $amount = $this->paymentAmount ?: $this->amount;
            $syntheticOrderId = 'WTU-' . now()->format('YmdHis') . '-' . substr(md5(uniqid('', true)),0,6);
            $payload = [
                'amount' => $amount,
                'currency' => $this->currency,
                'order_id' => $syntheticOrderId,
                'description' => 'Wallet Top-up',
                'crypto_currency' => $gateway === 'nowpayments' ? strtolower($this->selectedCrypto) : null,
                'metadata' => [
                    'wallet_topup' => true,
                    'customer_id' => $customer->id,
                ],
            ];
            $result = $service->createPayment($payload);
            if (empty($result['success'])) {
                $this->handlePaymentFailed(['error' => $result['error'] ?? 'Top-up failed']);
                return;
            }
            $data = $result['data'] ?? [];
            $redirectUrl = $data['payment_url'] ?? $data['invoice_url'] ?? $data['approval_url'] ?? null;
            if ($redirectUrl) {
                $this->alert('info', 'Redirecting to top-up invoice...');
                $this->dispatch('redirectAfterPayment', url: $redirectUrl, delay: 400);
                return redirect()->away($redirectUrl);
            }
            $this->handlePaymentCompleted($data);
        } catch (\Throwable $e) {
            Log::error('Wallet top-up internal flow failed', ['error' => $e->getMessage()]);
            $this->handlePaymentFailed(['error' => $e->getMessage()]);
        }
    }

    public function refundPayment($transactionId, $amount = null)
    {
        try {
            $payload = [
                'transaction_id' => $transactionId,
                'amount' => $amount
            ];
            $response = Http::post(url('/api/payment/refund'), $payload);
            $result = $response->json();
            if (isset($result['success']) && $result['success']) {
                $this->alert('success', 'Refund processed!');
                $this->refreshWallet();
            } else {
                $this->alert('error', $result['error'] ?? 'Refund failed');
            }
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function getPaymentStatus($orderId)
    {
        try {
            $response = Http::get(url('/api/payment/status/' . $orderId));
            $result = $response->json();
            if (isset($result['success']) && $result['success']) {
                return $result['data'] ?? null;
            } else {
                $this->alert('error', $result['error'] ?? 'Unable to fetch payment status');
                return null;
            }
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
            return null;
        }
    }

    // Merge duplicate getAvailableGateways into one method
    public function getAvailableGateways()
    {
        static $cacheTopup = null;
        static $cacheNormal = null;

        if ($this->isWalletTopup) {
            if ($cacheTopup !== null) return $cacheTopup;
        } else {
            if ($cacheNormal !== null) return $cacheNormal;
        }
        $mapped = [];
        try {
            // Primary source (could be remote/service definition)
            $service = app(\App\Services\PaymentGatewayService::class);
            $res = $service->getAvailableGateways();
            $data = $res['data'] ?? [];
            foreach ($data as $key => $info) {
                $mapped[$key] = [
                    'enabled' => true,
                    'name' => $info['name'] ?? ucfirst($key),
                    'fee' => 0.0,
                    'description' => $info['description'] ?? ($info['type'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('Primary gateway service failed', ['error'=>$e->getMessage()]);
        }

        // Overlay authoritative DB active states
        try {
            $dbMethods = \App\Models\PaymentMethod::select('slug','name','is_active')->get();
            $activeDbSlugs = [];
            foreach ($dbMethods as $m) {
                if ($m->is_active) {
                    $activeDbSlugs[] = $m->slug;
                    if (!isset($mapped[$m->slug])) {
                        $mapped[$m->slug] = [
                            'enabled' => true,
                            'name' => $m->name ?? ucfirst($m->slug),
                            'fee' => 0.0,
                            'description' => ''
                        ];
                    } else {
                        $mapped[$m->slug]['enabled'] = true;
                        if (!empty($m->name)) $mapped[$m->slug]['name'] = $m->name;
                    }
                }
            }
            // Prune any gateways not explicitly active in DB (except wallet added later for non-top-up)
            foreach ($mapped as $slug => $info) {
                if (!in_array($slug, $activeDbSlugs, true)) {
                    unset($mapped[$slug]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to overlay DB payment methods', ['error'=>$e->getMessage()]);
        }

        // Remove wallet for top-up; add for normal flow
        if ($this->isWalletTopup) {
            unset($mapped['wallet']);
        } else {
            $mapped['wallet'] = [
                'enabled' => true,
                'name' => 'Wallet Balance',
                'fee' => 0.0,
                'description' => 'Use internal wallet funds'
            ];
        }

        // Remove any disabled flags just in case
        foreach ($mapped as $k=>$info) {
            if (empty($info['enabled'])) unset($mapped[$k]);
        }

        // Preferred order
        $order = ['nowpayments','stripe','mir','paypal','wallet'];
        // For wallet top-up restrict strictly to active top-up gateways list
        if ($this->isWalletTopup) {
            $allowed = $this->getActiveTopupGateways();
            $mapped = array_filter($mapped, fn($v,$k) => in_array($k, $allowed, true), ARRAY_FILTER_USE_BOTH);
        }
        $ordered = [];
        foreach ($order as $pref) {
            if (isset($mapped[$pref])) $ordered[$pref] = $mapped[$pref];
        }
        // Append any remaining
        foreach ($mapped as $k=>$info) {
            if (!isset($ordered[$k])) $ordered[$k] = $info;
        }
        $mapped = $ordered;

        // Ensure a valid selected gateway for top-up
        if ($this->isWalletTopup && ($this->selectedGateway === 'wallet' || !isset($mapped[$this->selectedGateway]))) {
            $this->selectedGateway = array_key_first($mapped) ?: null;
        }

        if (empty($mapped)) {
            // As absolute fallback expose crypto if nothing else active
            $mapped = [
                'nowpayments' => [ 'enabled'=>true, 'name'=>'Cryptocurrency', 'fee'=>0.0, 'description'=>'Pay with crypto' ]
            ];
            if (!$this->isWalletTopup) {
                $mapped['wallet'] = [ 'enabled'=>true, 'name'=>'Wallet Balance', 'fee'=>0.0, 'description'=>'Use internal wallet funds' ];
            }
        }

        if ($this->isWalletTopup) {
            $cacheTopup = $mapped;
        } else {
            $cacheNormal = $mapped;
        }
        return $mapped;
    }

    /**
     * Active gateways for wallet top-up (exclude internal wallet).
     * Ordered by preference: nowpayments (crypto) -> stripe -> mir -> paypal.
     */
    private function getActiveTopupGateways(): array
    {
        try {
            $slugs = \App\Models\PaymentMethod::where('is_active', true)->pluck('slug')->toArray();
        } catch (\Throwable $e) {
            \Log::warning('Failed to load active payment methods for topup', ['error' => $e->getMessage()]);
            $slugs = [];
        }
        $ordered = [];
        foreach (['nowpayments','stripe','mir','paypal'] as $pref) {
            if (in_array($pref, $slugs, true)) $ordered[] = $pref;
        }
        return $ordered;
    }


    private function getFiatCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
        ];
    }

    #[On('paymentCompleted')]
    public function handlePaymentCompleted($result)
    {
        $this->processingPayment = false;
        $this->paymentStep = 'completed';

        // Only mark order paid if gateway actually confirmed success.
        if ($this->order) {
            $gateway = $this->selectedGateway;
            $explicitStatus = strtolower($result['status'] ?? $result['payment_status'] ?? '');
            $successfulStatuses = ['paid','finished','confirmed','succeeded','completed'];

            if ($gateway === 'nowpayments' && !in_array($explicitStatus, $successfulStatuses, true)) {
                // For NowPayments we rely on webhook to flip payment_status to paid.
                \Log::info('PaymentCompleted event received for NowPayments but status not final; skipping paid update', [
                    'order_id' => $this->order->id,
                    'reported_status' => $explicitStatus,
                ]);
            } else {
                $update = [
                    'payment_method' => $gateway,
                    'payment_transaction_id' => $result['transaction_id'] ?? $result['payment_id'] ?? null,
                    'paid_at' => now(),
                ];
                // Set both legacy and new status fields.
                $update['payment_status'] = 'paid';
                $update['status'] = 'completed';
                try {
                    $this->order->update($update);
                    // Queue provisioning only when payment is final
                    $this->dispatch('orderPaid', orderId: $this->order->id);
                } catch (\Throwable $e) {
                    \Log::error('Failed to update order as paid in PaymentProcessor', [
                        'order_id' => $this->order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->alert('success', 'Payment completed successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        // Refresh wallet balance
        $this->loadWalletBalance();

        // Redirect after a delay
    $this->dispatch('redirectAfterPayment', url: ($this->order ? route('my-orders.show', ['order_id' => $this->order->id]) : route('dashboard')), delay: 2000);
    }

    #[On('paymentFailed')]
    public function handlePaymentFailed($result)
    {
        $this->processingPayment = false;
        $this->paymentStep = 'failed';

        $errorMessage = $result['error'] ?? 'Payment failed. Please try again.';

        $this->alert('error', $errorMessage, [
            'position' => 'top-end',
            'timer' => 5000,
            'toast' => true,
        ]);

        // Update order status if order exists
        if ($this->order) {
            $this->order->update([
                'status' => 'payment_failed'
            ]);
        }
    }

    public function retryPayment()
    {
        $this->paymentStep = 'select_gateway';
        $this->processingPayment = false;
        $this->resetPaymentData();
    }

    public function selectCrypto($crypto)
    {
        $this->selectedCrypto = $crypto;
        $this->refreshCryptoEstimate();
    }

    private function refreshCryptoEstimate()
    {
        try {
            $service = app(\App\Services\PaymentGateways\NowPaymentsService::class);
            // We want to estimate how much selectedCrypto is needed to cover fiat amount
            $estimate = $service->estimatePrice(
                amount: $this->paymentAmount,
                currencyFrom: $this->selectedCurrency, // fiat
                currencyTo: $this->selectedCrypto     // crypto
            );
            if (($estimate['success'] ?? false) && isset($estimate['data']['amount_to'])) {
                $this->cryptoAmount = (float) $estimate['data']['amount_to'];
                // Check minimum amount
                $min = $service->getMinimumAmount($this->selectedCurrency, $this->selectedCrypto);
                if (($min['success'] ?? false) && isset($min['data']['min_amount'])) {
                    if ($this->paymentAmount < (float)$min['data']['min_amount']) {
                        $this->alert('warning', 'Minimum amount for this pair is '. $min['data']['min_amount'] .' '. strtoupper($this->selectedCurrency));
                    }
                }
            } else {
                // Fallback: keep previous or simple placeholder
                if ($this->cryptoAmount == 0) {
                    $this->cryptoAmount = 0.0;
                }
                if (isset($estimate['error'])) {
                    $msg = $estimate['error'];
                    // Shorten overly long messages
                    if (strlen($msg) > 160) {
                        $msg = substr($msg, 0, 157) . '...';
                    }
                    $this->alert('error', $msg);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to estimate crypto amount', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function loadWalletBalance()
    {
        if ($this->customer) {
            $this->walletBalance = $this->customer->balance ?? 0;
            $this->walletSufficient = $this->walletBalance >= $this->paymentAmount;
        }
    }

    private function resetPaymentData()
    {
        // Reset Stripe data
        $this->cardNumber = '';
        $this->expiryMonth = '';
        $this->expiryYear = '';
        $this->cvc = '';
        $this->cardholderName = '';

        // Reset crypto data
        $this->cryptoAmount = 0;
        $this->cryptoAddress = '';
        $this->paymentId = '';

        // Reset PayPal data
        $this->paypalOrderId = '';
    }

    private function getCryptoCurrencies()
    {
        return [
            'BTC' => ['name' => 'Bitcoin', 'symbol' => '₿'],
            'ETH' => ['name' => 'Ethereum', 'symbol' => 'Ξ'],
            'LTC' => ['name' => 'Litecoin', 'symbol' => 'Ł'],
            'XMR' => ['name' => 'Monero', 'symbol' => 'ɱ'],
            'SOL' => ['name' => 'Solana', 'symbol' => '◎']
        ];
    }

    private function getPaymentProgress()
    {
        $steps = [
            'select_gateway' => 'Select Payment Method',
            'processing' => 'Processing Payment',
            'completed' => 'Payment Completed',
            'failed' => 'Payment Failed'
        ];

        return [
            'current' => $this->paymentStep,
            'steps' => $steps,
            'progress' => match($this->paymentStep) {
                'select_gateway' => 25,
                'processing' => 50,
                'completed' => 100,
                'failed' => 0,
                default => 0
            }
        ];
    }

    // Format card number with spaces
    public function updatedCardNumber($value)
    {
        $this->cardNumber = preg_replace('/\s+/', '', $value);
        $this->cardNumber = preg_replace('/(\d{4})/', '$1 ', $this->cardNumber);
        $this->cardNumber = trim($this->cardNumber);
    }

    // Auto-focus next field
    public function focusNextField($field)
    {
        $this->dispatch('focusField', field: $field);
    }

    private function generateDemoCryptoAddress()
    {
        // Generate demo crypto addresses for different currencies
        $addresses = [
            'BTC' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            'ETH' => '0x742E4C4F6f72A49d8BCa60F02C1F8C49E2f6cA28',
            'LTC' => 'LQTpS3VaXCjPp4ZqnGnLv3i2N7n2MYP3XL',
            'XMR' => '44AFFq5kSiGBoZ4NMDwYtN18obc8AemS33DBLWs3H7otXft3XjrpDtQGv7SqSsaBYBb98uNbr2VBBEt7f2wfn3RVGQBEP3A',
            'SOL' => 'DYw8jCTfwHNRJhhmFcbXvVDTqWMEVFBX6ZKUmG5CNSKK'
        ];

        return $addresses[$this->selectedCrypto] ?? $addresses['BTC'];
    }
}