<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Order;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Http;

class PaymentProcessor extends Component
{
    use LivewireAlert;

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
    }

    public function render()
    {
        return view('livewire.components.payment-processor', [
            'availableGateways' => $this->getAvailableGateways(),
            'cryptoCurrencies' => $this->getCryptoCurrencies(),
            'paymentProgress' => $this->getPaymentProgress()
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
                $this->calculateCryptoAmount();
                break;
        }
    }

    public function processPayment()
    {
        $this->validate();
        $this->processingPayment = true;
        $this->paymentStep = 'processing';
        try {
            // For nowpayments, use selectedCrypto for crypto, else use selected currency for fiat
            $currency = $this->selectedGateway === 'nowpayments' ? $this->selectedCrypto : $this->currency;
            $payload = [
                'amount' => $this->paymentAmount,
                'currency' => $currency,
                'gateway' => $this->selectedGateway,
                'order_id' => $this->order->id ?? null,
                'wallet_topup' => $this->isWalletTopup ?? false,
                'metadata' => [
                    'user_id' => $this->customer ? $this->customer->id : null,
                    'email' => $this->customer ? $this->customer->email : null
                ]
            ];
            $response = Http::post(url('/api/payment/create'), $payload);
            $result = $response->json();
            if (is_array($result) && isset($result['success']) && $result['success']) {
                $this->handlePaymentCompleted($result['data'] ?? []);
            } else {
                $this->handlePaymentFailed(['error' => $result['error'] ?? 'Unknown error']);
            }
        } catch (\Exception $e) {
            $this->handlePaymentFailed(['error' => $e->getMessage()]);
        }
    }

    public function topUpWallet()
    {
        $this->validate();
        $this->is_submitting = true;
        try {
            $payload = [
                'amount' => $this->amount,
                'currency' => $this->currency,
                'gateway' => $this->selectedMethod,
            ];
            $response = Http::post(url('/api/payment/topup'), $payload);
            $result = $response->json();
            if (is_array($result) && isset($result['success']) && $result['success']) {
                $this->alert('success', 'Wallet top-up initiated!');
                $this->refreshWallet();
            } else {
                $this->alert('error', $result['error'] ?? 'Top-up failed');
            }
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
        $this->is_submitting = false;
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
            if (is_array($result) && isset($result['success']) && $result['success']) {
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
            if (is_array($result) && isset($result['success']) && $result['success']) {
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
        static $lastError = null;
        try {
            $response = \Illuminate\Support\Facades\Http::get(url('/api/payment/gateways'));
            $result = $response->json();
            if (is_array($result)) {
                if ((isset($result['success']) && $result['success'] && isset($result['data'])) || (!isset($result['success']) && !empty($result))) {
                    $gateways = $result['data'] ?? $result;
                    return !empty($gateways) ? $gateways : [
                        'stripe' => ['enabled' => true, 'name' => 'Stripe', 'fee' => 0.0],
                        'paypal' => ['enabled' => true, 'name' => 'PayPal', 'fee' => 0.0],
                        'mir' => ['enabled' => true, 'name' => 'Mir', 'fee' => 0.0],
                        'nowpayments' => ['enabled' => true, 'name' => 'Cryptocurrency', 'fee' => 0.0],
                        'wallet' => ['enabled' => true, 'name' => 'Wallet Balance', 'fee' => 0.0],
                    ];
                } else {
                    // Only alert once per session if error repeats
                    if ($lastError !== ($result['error'] ?? 'Unable to fetch gateways')) {
                        $this->alert('error', $result['error'] ?? 'Unable to fetch gateways');
                        $lastError = $result['error'] ?? 'Unable to fetch gateways';
                    }
                    return [
                        'stripe' => ['enabled' => true, 'name' => 'Stripe', 'fee' => 0.0],
                        'paypal' => ['enabled' => true, 'name' => 'PayPal', 'fee' => 0.0],
                        'mir' => ['enabled' => true, 'name' => 'Mir', 'fee' => 0.0],
                        'nowpayments' => ['enabled' => true, 'name' => 'Cryptocurrency', 'fee' => 0.0],
                        'wallet' => ['enabled' => true, 'name' => 'Wallet Balance', 'fee' => 0.0],
                    ];
                }
            } else {
                if ($lastError !== 'Unable to fetch gateways') {
                    $this->alert('error', 'Unable to fetch gateways');
                    $lastError = 'Unable to fetch gateways';
                }
                return [
                    'stripe' => ['enabled' => true, 'name' => 'Stripe', 'fee' => 0.0],
                    'paypal' => ['enabled' => true, 'name' => 'PayPal', 'fee' => 0.0],
                    'mir' => ['enabled' => true, 'name' => 'Mir', 'fee' => 0.0],
                    'nowpayments' => ['enabled' => true, 'name' => 'Cryptocurrency', 'fee' => 0.0],
                    'wallet' => ['enabled' => true, 'name' => 'Wallet Balance', 'fee' => 0.0],
                ];
            }
        } catch (\Exception $e) {
            if ($lastError !== $e->getMessage()) {
                $this->alert('error', $e->getMessage());
                $lastError = $e->getMessage();
            }
            return [
                'stripe' => ['enabled' => true, 'name' => 'Stripe', 'fee' => 0.0],
                'paypal' => ['enabled' => true, 'name' => 'PayPal', 'fee' => 0.0],
                'mir' => ['enabled' => true, 'name' => 'Mir', 'fee' => 0.0],
                'nowpayments' => ['enabled' => true, 'name' => 'Cryptocurrency', 'fee' => 0.0],
                'wallet' => ['enabled' => true, 'name' => 'Wallet Balance', 'fee' => 0.0],
            ];
        }
    }

    #[On('paymentCompleted')]
    public function handlePaymentCompleted($result)
    {
        $this->processingPayment = false;
        $this->paymentStep = 'completed';

        // Update order status if order exists
        if ($this->order) {
            $this->order->update([
                'status' => 'paid',
                'payment_method' => $this->selectedGateway,
                'payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? null,
                'paid_at' => now()
            ]);

            // Dispatch order processing
            $this->dispatch('orderPaid', orderId: $this->order->id);
        }

        $this->alert('success', 'Payment completed successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        // Refresh wallet balance
        $this->loadWalletBalance();

        // Redirect after a delay
        $this->dispatch('redirectAfterPayment', [
            'url' => $this->order ? route('orders.show', $this->order) : route('dashboard'),
            'delay' => 2000
        ]);
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
        $this->calculateCryptoAmount();
    }

    private function calculateCryptoAmount()
    {
        // This would normally call a crypto price API
        $rates = [
            'BTC' => 45000,
            'ETH' => 3000,
            'LTC' => 150,
            'XMR' => 200,
            'SOL' => 100
        ];

        $rate = $rates[$this->selectedCrypto] ?? 1;
        $this->cryptoAmount = round($this->paymentAmount / $rate, 8);
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