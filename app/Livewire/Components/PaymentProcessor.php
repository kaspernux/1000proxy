<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\Order;
use App\Models\User;
use App\Services\PaymentGateways\StripePaymentService;
use App\Services\PaymentGateways\PayPalPaymentService;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PaymentProcessor extends Component
{
    use LivewireAlert;

    #[Reactive]
    public $order;

    #[Reactive]
    public $user;

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

    public function mount(Order $order = null, User $user = null)
    {
        $this->order = $order;
        $this->user = $user ?? \Illuminate\Support\Facades\Auth::guard('customer')->user();
        $this->paymentAmount = $order ? $order->total_amount : 0;
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
            switch ($this->selectedGateway) {
                case 'stripe':
                    $result = $this->processStripePayment();
                    break;
                case 'paypal':
                    $result = $this->processPayPalPayment();
                    break;
                case 'nowpayments':
                    $result = $this->processNowPayment();
                    break;
                case 'wallet':
                    $result = $this->processWalletPayment();
                    break;
                default:
                    throw new \Exception('Invalid payment gateway selected');
            }

            if ($result['success']) {
                $this->handlePaymentCompleted($result);
            } else {
                $this->handlePaymentFailed($result);
            }
        } catch (\Exception $e) {
            $this->handlePaymentFailed(['error' => $e->getMessage()]);
        }
    }

    private function processStripePayment()
    {
        $stripeService = app(StripePaymentService::class);

        return $stripeService->createPayment([
            'amount' => $this->paymentAmount * 100, // Convert to cents
            'currency' => 'usd',
            'payment_method' => [
                'card' => [
                    'number' => $this->cardNumber,
                    'exp_month' => $this->expiryMonth,
                    'exp_year' => $this->expiryYear,
                    'cvc' => $this->cvc
                ],
                'billing_details' => [
                    'name' => $this->cardholderName,
                    'email' => $this->user->email
                ]
            ],
            'confirm' => true,
            'metadata' => [
                'order_id' => $this->order->id ?? '',
                'user_id' => $this->user->id
            ]
        ]);
    }

    private function processPayPalPayment()
    {
        $paypalService = app(PayPalPaymentService::class);

        return $paypalService->createPayment([
            'amount' => $this->paymentAmount,
            'currency' => 'USD',
            'description' => $this->order ? "Order #{$this->order->id}" : 'Wallet Top-up',
            'return_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
            'metadata' => [
                'order_id' => $this->order->id ?? '',
                'user_id' => $this->user->id
            ]
        ]);
    }

    private function processNowPayment()
    {
        // For now, simulate crypto payment creation
        // This would integrate with NowPayments API when available
        return [
            'success' => true,
            'payment_url' => 'https://nowpayments.io/payment/demo',
            'payment_id' => 'np_' . uniqid(),
            'crypto_amount' => $this->cryptoAmount,
            'crypto_currency' => $this->selectedCrypto,
            'payment_address' => $this->generateDemoCryptoAddress()
        ];
    }

    private function processWalletPayment()
    {
        if (!$this->walletSufficient) {
            return [
                'success' => false,
                'error' => 'Insufficient wallet balance'
            ];
        }

        try {
            // Simple wallet debit - in production this would use a proper wallet service
            $newBalance = $this->user->balance - $this->paymentAmount;
            $this->user->update(['balance' => $newBalance]);

            // Create transaction record (assuming a transactions table exists)
            $transactionId = 'wallet_' . uniqid();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_method' => 'wallet',
                'amount' => $this->paymentAmount
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
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
        if ($this->user) {
            $this->walletBalance = $this->user->balance ?? 0;
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

    private function getAvailableGateways()
    {
        return [
            'wallet' => [
                'name' => 'Wallet Balance',
                'icon' => '💰',
                'description' => 'Pay with your account balance',
                'enabled' => true,
                'fee' => 0
            ],
            'stripe' => [
                'name' => 'Credit/Debit Card',
                'icon' => '💳',
                'description' => 'Visa, Mastercard, American Express',
                'enabled' => config('services.stripe.key') !== null,
                'fee' => 2.9
            ],
            'paypal' => [
                'name' => 'PayPal',
                'icon' => '🏛️',
                'description' => 'Pay with PayPal account or card',
                'enabled' => config('services.paypal.client_id') !== null,
                'fee' => 3.4
            ],
            'nowpayments' => [
                'name' => 'Cryptocurrency',
                'icon' => '₿',
                'description' => 'Bitcoin, Ethereum, and more',
                'enabled' => config('services.nowpayments.api_key') !== null,
                'fee' => 1.0
            ]
        ];
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
