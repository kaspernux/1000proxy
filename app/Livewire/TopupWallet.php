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
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\WalletTransaction;

#[Title('Top Up Wallet - 1000 PROXIES')]
class TopupWallet extends Component
{
    use WithFileUploads, LivewireAlert;

    // Wallet topup properties
    public $currency;
    public $amount;
    public $reference;
    public $wallet;
    public $selectedMethod = 'crypto';

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

        if ($this->selectedMethod === 'crypto' && $this->currentStep >= 3) {
            $rules['paymentProof'] = 'nullable|image|max:5120'; // 5MB max
        }

        if ($this->recurringTopup) {
            $rules['recurringAmount'] = 'required|numeric|min:10|max:10000';
            $rules['recurringFrequency'] = 'required|in:weekly,monthly,quarterly';
        }

        return $rules;
    }

    public function mount($currency = 'btc')
    {
        // Check authentication
        if (!Auth::guard('customer')->check()) {
            return redirect('/login');
        }

        $currency = strtolower($currency);

        $supportedCurrencies = ['btc', 'eth', 'usdt', 'xmr', 'sol', 'bnb'];
        if (!in_array($currency, $supportedCurrencies)) {
            $currency = 'btc'; // Default fallback
        }

        $this->currency = $currency;

        // Get customer's wallet (only customers have wallets)
        $customer = Auth::guard('customer')->user();
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
                $seconds = RateLimiter::availableAt($key) - time();
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
        switch ($this->selectedMethod) {
            case 'crypto':
                $this->generateCryptoAddress();
                break;
            case 'bank':
                $this->generateBankDetails();
                break;
            case 'paypal':
                $this->generatePayPalDetails();
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

        $this->cryptoAddress = $addresses[$this->currency] ?? $addresses['btc'];
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
            // Rate limiting
            $key = 'topup_submit.' . Auth::guard('customer')->id();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableAt($key) - time();
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

            RateLimiter::hit($key, 300); // 5-minute window

            $this->reference = $this->reference ?: 'topup_' . strtoupper(Str::random(10));

            // Calculate final amount with bonus
            $bonus = $this->calculateBonus();
            $finalAmount = $this->amount + $bonus;

            // Handle payment proof upload
            $proofPath = null;
            if ($this->paymentProof) {
                $proofPath = $this->paymentProof->store('payment-proofs', 'private');
            }

            // Create transaction record
            $transaction = WalletTransaction::create([
                'wallet_id' => $this->wallet->id,
                'type' => 'deposit',
                'amount' => $this->amount,
                'bonus_amount' => $bonus,
                'final_amount' => $finalAmount,
                'currency' => strtoupper($this->currency),
                'status' => 'pending',
                'reference' => $this->reference,
                'description' => 'Wallet top-up using ' . strtoupper($this->currency),
                'meta' => [
                    'payment_method' => $this->selectedMethod,
                    'crypto_address' => $this->cryptoAddress,
                    'payment_proof_path' => $proofPath,
                    'promotion_applied' => $this->selectedPromotion,
                    'notes' => $this->notes,
                    'notify_on_completion' => $this->notifyOnCompletion,
                ],
            ]);

            // Setup recurring topup if enabled
            if ($this->recurringTopup) {
                $this->setupRecurringTopup($transaction);
            }

            // Clear rate limit on success
            RateLimiter::clear($key);

            $this->currentStep = 4;
            $this->loadTransactionHistory();
            $this->is_submitting = false;

            $this->alert('success', '✅ Deposit request submitted successfully! We will process it within 1-24 hours.', [
                'position' => 'bottom-end',
                'timer' => 5000,
                'toast' => true,
            ]);

            // Security logging
            Log::info('Wallet top-up initiated', [
                'customer_id' => Auth::guard('customer')->id(),
                'amount' => $this->amount,
                'promo_code' => $this->promoCode,
                'payment_method' => $this->paymentMethod,
                'ip' => request()->ip(),
            ]);

            $this->dispatch('submitEnded');
            $this->dispatch('refreshWallet');

        } catch (ValidationException $e) {
            $this->is_submitting = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_submitting = false;
            Log::error('Topup submission error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'amount' => $this->amount,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'An error occurred while submitting your topup request. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 5000,
                'toast' => true,
            ]);
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

    // Get payment methods
    public function getPaymentMethods()
    {
        return [
            'crypto' => [
                'name' => 'Cryptocurrency',
                'description' => 'Pay with Bitcoin, Ethereum, and other cryptocurrencies',
                'processing_time' => '1-6 hours',
                'fees' => 'Network fees apply',
            ],
            'bank' => [
                'name' => 'Bank Transfer',
                'description' => 'Direct bank wire transfer',
                'processing_time' => '1-3 business days',
                'fees' => 'No additional fees',
            ],
            'paypal' => [
                'name' => 'PayPal',
                'description' => 'Pay with your PayPal account',
                'processing_time' => '5-15 minutes',
                'fees' => '3.5% + $0.30',
            ],
        ];
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login');
        }

        $this->wallet = $customer->wallet ?? $customer->getWallet();

        return view('livewire.topup-wallet', [
            'wallet' => $this->wallet,
            'supportedCurrencies' => $this->getSupportedCurrencies(),
            'paymentMethods' => $this->getPaymentMethods(),
            'calculatedBonus' => $this->calculateBonus(),
        ]);
    }
}