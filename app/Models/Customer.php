<?php

namespace App\Models;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Helpers\CartManagement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $guard = 'customer';

    protected $table = 'customers';

    protected $fillable = [
        'is_active',
        'image',
        'name',
        'email',
        'password',
        'telegram_chat_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'refcode',
        'date',
        'phone',
        'refered_by',
        'step',
        'freetrial',
        'first_start',
        'temp',
        'is_agent',
        'discount_percent',
        'agent_date',
        'spam_info',
        'locale',
        'theme_mode',
        'email_notifications',
        'timezone',
        'suspended_at',
        'suspension_reason',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'telegram_chat_id',
        'refcode',
        'temp',
        'spam_info',
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::created(function ($customer) {
            $wallet = $customer->wallet()->create([
                'balance' => 0,
                'is_default' => true,
            ]);

            $wallet->generateDepositAddresses();
        });
    }

    public function orders() { return $this->hasMany(Order::class); }
    public function reviews() { return $this->hasMany(ServerReview::class); }
    public function ratings() { return $this->hasMany(ServerRating::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function paymentMethods() { return $this->hasMany(PaymentMethod::class); }
    public function referrer() { return $this->belongsTo(Customer::class, 'refered_by'); }
    public function referredCustomers() { return $this->hasMany(Customer::class, 'refered_by'); }
    public function clients() { return $this->hasMany(ServerClient::class); }
    public function traffics() { return $this->hasMany(ClientTraffic::class); }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function getWallet(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create(['balance' => 0]);
    }

    public function hasSufficientWalletBalance($amount): bool
    {
        return $this->getWallet()->balance >= $amount;
    }

    public function payFromWallet($amount, $description = 'Order Payment'): bool
    {
        $wallet = $this->getWallet();

        if (bccomp((string) $wallet->balance, (string) $amount, 8) < 0) {
            Log::warning("❌ Attempt to overdraw wallet for customer ID {$this->id}");
            return false;
        }

        $wallet->decrement('balance', $amount);

        $wallet->transactions()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $this->id,
            'amount' => -$amount,
            'type' => 'debit',
            'status' => 'completed',
            'reference' => 'wallet_' . strtoupper(Str::random(8)),
            'description' => $description,
        ]);

        return true;
    }


    public function addToWallet($amount, $description = 'Top-up'): void
    {
        $wallet = $this->getWallet();
        $wallet->increment('balance', $amount);
        $wallet->transactions()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $this->id,
            'amount' => $amount,
            'type' => 'credit',
            'status' => 'completed',
            'reference' => 'wallet_' . strtoupper(Str::random(8)),
            'description' => $description,
        ]);
    }


    public function getWalletAddress($currency)
    {
        $wallet = $this->getWallet();
        return [
            'address' => $wallet->address,
            'deposit_tag' => $wallet->deposit_tag,
        ];
    }

    public function getWalletBalance($currency)
    {
        $wallet = $this->getWallet();
        return [
            'balance' => $wallet->balance,
        ];
    }

    public function getWalletQrCode($currency)
    {
        $wallet = $this->getWallet();
        try {
            $qrService = app(\App\Services\QrCodeService::class);
            $qrCode = $qrService->generateBase64QrCode($wallet->address, 250, [
                'colorScheme' => 'primary',
                'style' => 'square'
            ]);
            return [
                'qr_code' => $qrCode,
            ];
        } catch (\Exception $e) {
            // Fallback to text representation
            return [
                'qr_code' => 'data:text/plain;base64,' . base64_encode($wallet->address),
            ];
        }
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hasclients()
    {
        // replicate exactly your filter logic:
        return $this->hasMany(ServerClient::class, /* foreignKey */ 'email', /* localKey */ 'id')
                    ->where('email', 'LIKE', '%#ID ' . $this->id);
    }

    public function autoCreateOrderFromDeposit(WalletTransaction $transaction): void
    {
        // 🛡 Prevent duplicate execution
        if (isset($transaction->metadata['order_created']) && $transaction->metadata['order_created']) {
            Log::warning("🚫 Order already created for this transaction: {$transaction->reference}");
            return;
        }

        $cartItems = CartManagement::getCartItemsFromCookie();

        if (empty($cartItems)) {
            Log::info("❌ No cart items found for auto-order (customer ID: {$this->id})");
            return;
        }

        $total = CartManagement::calculateGrandTotal($cartItems);

        if (bccomp((string) $transaction->amount, (string) $total, 8) < 0) {
            Log::info("❌ Deposit insufficient for auto-order (deposit: {$transaction->amount}, total: {$total})");
            return;
        }

        // ✅ Get wallet and check balance
        $wallet = $this->getWallet();
        if (bccomp((string) $wallet->balance, (string) $total, 8) < 0) {
            Log::info("❌ Wallet balance insufficient for auto-order (balance: {$wallet->balance})");
            return;
        }

        // ✅ Use default wallet payment method
        $paymentMethod = \App\Models\PaymentMethod::where('slug', 'wallet')->first();

        $order = \App\Models\Order::create([
            'customer_id' => $this->id,
            'grand_amount' => $total,
            'currency' => 'usd',
            'payment_method' => $paymentMethod->id ?? null,
            'order_status' => 'new',
            'payment_status' => 'paid',
            'notes' => 'Auto-generated from deposit transaction ' . $transaction->reference,
        ]);

        $invoice = \App\Models\Invoice::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id ?? null,
            'price_amount' => $total,
            'price_currency' => 'usd',
            'pay_amount' => $total,
            'pay_currency' => 'usd',
            'order_description' => $order->notes,
            'invoice_url' => '',
            'success_url' => route('success', ['order' => $order->id]),
            'cancel_url' => route('cancel', ['order' => $order->id]),
            'is_fixed_rate' => true,
            'is_fee_paid_by_user' => true,
        ]);

        foreach ($cartItems as $item) {
            $plan = \App\Models\ServerPlan::findOrFail($item['server_plan_id']);
            $order->items()->create([
                'server_plan_id' => $item['server_plan_id'],
                'quantity' => $item['quantity'],
                'unit_amount' => $plan->price,
                'total_amount' => $plan->price * $item['quantity'],
            ]);
        }

        // 💳 Deduct balance safely
        $wallet->decrement('balance', $total);
        $wallet->transactions()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $this->id,
            'amount' => -$total,
            'type' => 'debit',
            'status' => 'completed',
            'reference' => 'wallet_' . strtoupper(Str::random(8)),
            'description' => 'Auto-paid from confirmed deposit for Order #' . $order->id,
        ]);

        CartManagement::clearCartItems();

        // ✅ Process XUI clients
        try {
            app(\App\Livewire\CheckoutPage::class)->processXui($order);
            $order->markAsCompleted();

            // ✅ Safely update metadata after order creation
            $transaction->update([
                'metadata' => array_merge($transaction->metadata ?? [], ['order_created' => true]),
            ]);

            Log::info("✅ Auto-order created and XUI clients provisioned for {$this->email}");
        } catch (\Exception $e) {
            Log::error("⚠️ XUI provisioning failed during auto-order: " . $e->getMessage());
        }
    }

    /**
     * Check if customer has Telegram linked
     */
    public function hasTelegramLinked(): bool
    {
        return !empty($this->telegram_chat_id);
    }

    /**
     * Get Telegram display name
     */
    public function getTelegramDisplayName(): string
    {
        if ($this->telegram_first_name && $this->telegram_last_name) {
            return $this->telegram_first_name . ' ' . $this->telegram_last_name;
        }

        return $this->telegram_first_name ?: $this->telegram_username ?: 'Unknown';
    }

    /**
     * Link Telegram account
     */
    public function linkTelegram(int $chatId, ?string $username = null, ?string $firstName = null, ?string $lastName = null): void
    {
        $this->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_first_name' => $firstName,
            'telegram_last_name' => $lastName,
        ]);
    }

    /**
     * Unlink Telegram account
     */
    public function unlinkTelegram(): void
    {
        $this->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_first_name' => null,
            'telegram_last_name' => null,
        ]);
    }

}
