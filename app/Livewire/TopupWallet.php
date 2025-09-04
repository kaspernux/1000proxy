<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Livewire\Traits\LivewireAlertV4;
use App\Models\WalletTransaction;

#[Title('Top Up Wallet - 1000 PROXIES')]
class TopupWallet extends Component
{
    use WithFileUploads, LivewireAlertV4;

    // Wallet topup properties
    public $currency; // legacy crypto symbol for backwards compat
    public $amount;
    public $reference;
    public $wallet;
    // New unified payment API (align with checkout)
    public $payment_method = 'crypto'; // crypto|stripe|paypal|mir (no wallet for topup)
    public $crypto_currency = 'btc';
    public $fiat_currency = 'USD';

    // Loading states
    public $is_loading = false;
    public $is_submitting = false;

    // Payment method specific properties
    public $cryptoAddress = '';
    public $paymentProof;
    public $notes = '';

    // Preset amounts
    public $presetAmounts = [10, 25, 50, 100, 250, 500, 1000, 2500];

    // Transaction tracking
    public $pendingTransactions = [];
    public $completedTransactions = [];

    // UI states
    public $showPaymentModal = false;
    public $showTransactionHistory = false;
    public $currentStep = 1; // 1: Amount, 2: Method, 3: Payment, 4: Confirmation

    // Promotional offers
    public $activePromotions = [];
    public $selectedPromotion = null;
    public $promoCode = '';

    // Advanced features
    public $recurringTopup = false;
    public $recurringAmount = null;
    public $recurringFrequency = 'monthly';
    public $notifyOnCompletion = true;

    protected $listeners = ['refreshWallet' => '$refresh'];

    protected function rules()
    {
        $rules = [
            'amount' => 'required|numeric|min:1|max:50000',
            'reference' => 'nullable|string|unique:wallet_transactions,reference',
            'notes' => 'nullable|string|max:500',
        ];

        if ($this->payment_method === 'crypto' && $this->currentStep >= 3) {
            $rules['paymentProof'] = 'nullable|image|max:5120'; // 5MB max
        }

        // Validate selected currency depending on method
        if ($this->payment_method === 'crypto') {
            $rules['crypto_currency'] = 'required|in:btc,eth,xmr,ltc,doge,ada,dot,sol,usdt,usdc,bnb';
        } else {
            $rules['fiat_currency'] = 'required|in:USD,EUR,GBP,RUB';
        }

        if ($this->recurringTopup) {
            $rules['recurringAmount'] = 'required|numeric|min:10|max:10000';
            $rules['recurringFrequency'] = 'required|in:weekly,monthly,quarterly';
        }

        return $rules;
    }

    public function mount($currency = 'btc')
    {
        // Check authentication using customer guard
        if (!\Illuminate\Support\Facades\Auth::guard('customer')->check()) {
            return redirect('/login');
        }

        $currency = strtolower($currency);
        $supportedCurrencies = ['btc', 'eth', 'usdt', 'xmr', 'sol', 'bnb'];
        if (!in_array($currency, $supportedCurrencies)) {
            $currency = 'btc'; // Default fallback
        }
        $this->currency = $currency;
        $this->crypto_currency = $currency;
        // Prefer crypto when available (NowPayments)
        $available = $this->getAvailablePaymentMethods();
        if (in_array('crypto', $available, true)) {
            $this->payment_method = 'crypto';
        } elseif (!empty($available)) {
            $this->payment_method = $available[0];
        }
        // Get customer's wallet (only customers have wallets)
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
        $this->wallet = $customer->wallet ?? $customer->getWallet();
        // Load user's transaction history
        $this->loadTransactionHistory();
        // Load active promotions
        $this->loadActivePromotions();
    }

    // Load transaction history
    public function loadTransactionHistory()
    {
        if (!$this->wallet) return;

        $this->pendingTransactions = WalletTransaction::where('wallet_id', $this->wallet->id)
                                                    ->where('type', 'deposit')
                                                    ->where('status', 'pending')
                                                    ->latest()
                                                    ->take(5)
                                                    ->get();

        $this->completedTransactions = WalletTransaction::where('wallet_id', $this->wallet->id)
                                                       ->where('type', 'deposit')
                                                       ->where('status', 'completed')
                                                       ->latest()
                                                       ->take(10)
                                                       ->get();
    }

    // Load active promotions
    public function loadActivePromotions()
    {
        // Mock promotions - replace with actual model
        $this->activePromotions = [
            [
                'id' => 1,
                'title' => 'First Time Bonus',
                'description' => 'Get 10% extra on your first deposit',
                'bonus_percentage' => 10,
                'min_amount' => 50,
                'max_bonus' => 100,
                'code' => 'FIRST10',
            ],
            [
                'id' => 2,
                'title' => 'Big Spender',
                'description' => 'Deposit $500+ and get 5% bonus',
                'bonus_percentage' => 5,
                'min_amount' => 500,
                'max_bonus' => 250,
                'code' => 'BIG5',
            ],
        ];
    }

    // Set preset amount
    public function setAmount($amount)
    {
        $this->amount = $amount;
        $this->calculateBonus();
    }

    // Calculate bonus based on selected promotion
    public function calculateBonus()
    {
        if (!$this->selectedPromotion || !$this->amount) {
            return 0;
        }

        $promotion = collect($this->activePromotions)->firstWhere('id', $this->selectedPromotion);
        if (!$promotion || $this->amount < $promotion['min_amount']) {
            return 0;
        }

        $bonus = ($this->amount * $promotion['bonus_percentage']) / 100;
        return min($bonus, $promotion['max_bonus']);
    }

    // Apply promo code
    public function applyPromoCode()
    {
        try {
            // Rate limiting
            $key = 'promo_apply.' . Auth::guard('customer')->id();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                throw ValidationException::withMessages([
                    'promoCode' => ["Too many promo code attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $promotion = collect($this->activePromotions)->firstWhere('code', strtoupper($this->promoCode));

            if (!$promotion) {
                RateLimiter::hit($key, 300);
                $this->alert('error', 'Invalid promo code.', [
                    'position' => 'bottom-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
                return;
            }

            if ($this->amount < $promotion['min_amount']) {
                $this->alert('error', "Minimum deposit amount for this promo is ${$promotion['min_amount']}", [
                    'position' => 'bottom-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
                return;
            }

            $this->selectedPromotion = $promotion['id'];
            RateLimiter::clear($key);
            
            $this->alert('success', "Promo code applied! You'll get {$promotion['bonus_percentage']}% bonus.", [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Promo code application error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'promo_code' => $this->promoCode,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to apply promo code. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    // Navigation methods
    public function nextStep()
    {
        $this->is_loading = true;

        try {
            if ($this->currentStep === 1) {
                $this->validate(['amount' => 'required|numeric|min:1|max:50000']);
            } elseif ($this->currentStep === 2) {
                $this->generatePaymentDetails();
            }

            $this->currentStep++;
            $this->is_loading = false;

        } catch (ValidationException $e) {
            $this->is_loading = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading = false;
            Log::error('Topup step navigation error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::id(),
                'step' => $this->currentStep,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'An error occurred. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function previousStep()
    {
        $this->currentStep--;
    }

    public function setStep($step)
    {
        $this->currentStep = $step;
    }

    // Generate payment details based on selected method
    public function generatePaymentDetails()
    {
        switch ($this->payment_method) {
            case 'crypto':
                $this->generateCryptoAddress();
                break;
            case 'stripe':
            case 'paypal':
            case 'mir':
                // details are generated on gateway side
                break;
        }
    }

    private function generateCryptoAddress()
    {
        // Mock crypto addresses - replace with actual address generation
        $addresses = [
            'btc' => '1A2B3C4D5E6F7G8H9I0J1K2L3M4N5O6P7Q8R9S',
            'eth' => '0x1234567890123456789012345678901234567890',
            'usdt' => '0xabcdefabcdefabcdefabcdefabcdefabcdefabcd',
            'xmr' => '47CL7FiLT9sJdkP9aTq1uyKR2N3RnvG5qRk2MF8E9nXhX3BnGt',
            'sol' => 'DjVE6JNiYqPL2QXs9XGvXqL5FnGqXtV6s8X5sKp9fVcF',
            'bnb' => 'bnb1abcdefghijklmnopqrstuvwxyz1234567890',
        ];

    $this->cryptoAddress = $addresses[$this->crypto_currency] ?? $addresses['btc'];
    }

    private function generateBankDetails()
    {
        // Mock bank details
    }

    private function generatePayPalDetails()
    {
        // Mock PayPal details
    }

    // Submit topup request
    public function submitTopup()
    {
        $this->is_submitting = true;

        try {
            $key = 'topup_submit.' . Auth::guard('customer')->id();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                throw ValidationException::withMessages([
                    'amount' => ["Too many topup attempts. Please try again in {$seconds} seconds."],
                ]);
            }
            $this->validate();
            if (!$this->wallet) {
                $this->alert('error', 'Wallet not found. Please contact support.', [
                    'position' => 'bottom-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
                $this->is_submitting = false;
                return;
            }
            RateLimiter::hit($key, 300);
            $this->reference = $this->reference ?: 'topup_' . strtoupper(Str::random(10));
            $bonus = $this->calculateBonus();
            $finalAmount = $this->amount + $bonus;
            $proofPath = null;
            if ($this->paymentProof) {
                $proofPath = $this->paymentProof->store('payment-proofs', 'private');
            }
            // Map generic method to concrete gateway key
            $gateway = $this->payment_method;
            if ($this->payment_method === 'crypto') {
                $keys = array_keys(config('payment_gateways.gateways', []));
                if (in_array('nowpayments', $keys, true)) {
                    $gateway = 'nowpayments';
                } elseif (in_array('coinbase', $keys, true)) {
                    $gateway = 'coinbase';
                } else {
                    $gateway = 'nowpayments';
                }
            }

            // Prepare payload for PaymentController
            $payload = [
                'amount' => $this->amount,
                // Always send fiat price currency; pay coin is provided via crypto_currency for crypto payments
                'currency' => strtoupper($this->fiat_currency),
                'gateway' => $gateway,
                'reference' => $this->reference,
                'bonus_amount' => $bonus,
                'final_amount' => $finalAmount,
                'promo_code' => $this->promoCode,
                'notes' => $this->notes,
                'payment_proof_path' => $proofPath,
                'promotion_applied' => $this->selectedPromotion,
                'notify_on_completion' => $this->notifyOnCompletion,
                'payment_method' => $this->payment_method,
                'crypto_currency' => $this->payment_method === 'crypto' ? $this->crypto_currency : null,
                'fiat_currency' => $this->payment_method !== 'crypto' ? $this->fiat_currency : null,
            ];
                // Call PaymentController directly to preserve session auth
                $controller = app(\App\Http\Controllers\PaymentController::class);
                $request = new \Illuminate\Http\Request();
                $request->replace($payload);
                $jsonResponse = $controller->topUpWallet($request);
                $result = method_exists($jsonResponse, 'getData') ? (array) $jsonResponse->getData(true) : [];
                if (($result['success'] ?? false) === true) {
                // Redirect to gateway invoice URL when available (NowPayments)
                    $paymentPayload = $result['data']['payment'] ?? [];
                    $paymentUrl = $paymentPayload['payment_url']
                        ?? ($result['data']['payment_url'] ?? ($result['redirect_url'] ?? null));
                // Store pending transaction info for polling (if payment_id present)
                if (!empty($paymentPayload['payment_id'])) {
                    $this->pendingTransactions[] = (object) [
                        'id' => $result['data']['transaction_id'] ?? null,
                        'payment_id' => $paymentPayload['payment_id'],
                        'payment_url' => $paymentPayload['payment_url'] ?? null,
                        'status' => $paymentPayload['status'] ?? 'pending',
                        'amount' => $paymentPayload['amount'] ?? $this->amount,
                    ];
                }
                if ($paymentUrl) {
                    return redirect()->away($paymentUrl);
                }
                $this->currentStep = 4;
                $this->loadTransactionHistory();
                $this->is_submitting = false;
                $this->alert('success', '✅ Deposit request submitted successfully!', [
                    'position' => 'bottom-end',
                    'timer' => 5000,
                    'toast' => true,
                ]);
                Log::info('Wallet top-up initiated via PaymentController', [
                    'customer_id' => Auth::guard('customer')->id(),
                    'amount' => $this->amount,
                    'promo_code' => $this->promoCode,
                    'gateway' => $gateway,
                    'ip' => request()->ip(),
                ]);
                $this->dispatch('submitEnded');
                $this->dispatch('refreshWallet');
            } else {
                $this->is_submitting = false;
                $errorMsg = $result['error'] ?? 'An error occurred while submitting your topup request. Please try again.';
                $this->alert('error', $errorMsg, [
                    'position' => 'bottom-end',
                    'timer' => 5000,
                    'toast' => true,
                ]);
                Log::error('Topup submission error via PaymentController', [
                    'error' => $errorMsg,
                    'customer_id' => Auth::guard('customer')->id(),
                    'amount' => $this->amount,
                    'gateway' => $gateway,
                    'ip' => request()->ip()
                ]);
            }
        } catch (ValidationException $e) {
            $this->is_submitting = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_submitting = false;
            Log::error('Topup submission error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'amount' => $this->amount,
                'gateway' => $gateway ?? $this->payment_method,
                'ip' => request()->ip()
            ]);
            $this->alert('error', 'An error occurred while submitting your topup request. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        }
    }

    /**
     * Poll payment status for a NowPayments payment_id
     */
    public function pollPaymentStatus(string $paymentId)
    {
    try {
        // Call service directly to avoid needing session cookies for internal HTTP call
        $nowService = app(\App\Services\PaymentGateways\NowPaymentsService::class);
        $res = $nowService->verifyPayment($paymentId);
        $data = $res['data'] ?? [];
            // Update pendingTransactions entry if exists
            foreach ($this->pendingTransactions as &$pt) {
                if (($pt->payment_id ?? null) === $paymentId) {
            $pt->status = $data['status'] ?? $data['payment_status'] ?? $pt->status;
                    // If finished/confirmed, move to completed
                    if (in_array($pt->status, ['finished','confirmed','paid','completed'])) {
                        $this->completedTransactions[] = $pt;
                        $this->pendingTransactions = array_filter($this->pendingTransactions, fn($x) => ($x->payment_id ?? null) !== $paymentId);
                        $this->loadTransactionHistory();
                    }
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Poll payment status failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
        }
    }

    private function setupRecurringTopup($initialTransaction)
    {
        // Create recurring topup schedule
        // This would typically create a scheduled task or cron job
    }

    // Cancel pending transaction
    public function cancelTransaction($transactionId)
    {
        $transaction = WalletTransaction::where('id', $transactionId)
                                       ->where('wallet_id', $this->wallet->id)
                                       ->where('status', 'pending')
                                       ->first();

        if ($transaction) {
            $transaction->update(['status' => 'cancelled']);
            $this->loadTransactionHistory();

            $this->alert('success', 'Transaction cancelled successfully.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    // Get supported currencies
    public function getSupportedCurrencies()
    {
        return [
            'btc' => ['name' => 'Bitcoin', 'symbol' => 'BTC', 'icon' => '₿'],
            'eth' => ['name' => 'Ethereum', 'symbol' => 'ETH', 'icon' => 'Ξ'],
            'usdt' => ['name' => 'Tether', 'symbol' => 'USDT', 'icon' => '₮'],
            'xmr' => ['name' => 'Monero', 'symbol' => 'XMR', 'icon' => 'ɱ'],
            'sol' => ['name' => 'Solana', 'symbol' => 'SOL', 'icon' => '◎'],
            'bnb' => ['name' => 'Binance Coin', 'symbol' => 'BNB', 'icon' => 'BNB'],
        ];
    }

    // Available methods based on configured gateways (exclude wallet for top-ups)
    public function getAvailablePaymentMethods()
    {
        $keys = array_keys(config('payment_gateways.gateways', []));
        $methods = [];
        if (in_array('nowpayments', $keys, true) || in_array('coinbase', $keys, true)) {
            $methods[] = 'crypto';
        }
        if (in_array('stripe', $keys, true)) {
            $methods[] = 'stripe';
        }
        if (in_array('paypal', $keys, true)) {
            $methods[] = 'paypal';
        }
        if (in_array('mir', $keys, true)) {
            $methods[] = 'mir';
        }
        return $methods;
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login');
        }

        $this->wallet = $customer->wallet ?? $customer->getWallet();

    return view('livewire.topup', [
            'wallet' => $this->wallet,
            'supportedCurrencies' => $this->getSupportedCurrencies(),
            'availableMethods' => $this->getAvailablePaymentMethods(),
            'calculatedBonus' => $this->calculateBonus(),
        ]);
    }
}