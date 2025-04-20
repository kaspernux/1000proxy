<?php

namespace App\Models;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        'tgId',
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
        'spam_info'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::created(function ($customer) {
            Wallet::createCustomerWallet($customer->id, 'BTC', 'BTC', true);
            Wallet::createCustomerWallet($customer->id, 'XMR', 'XMR');
            Wallet::createCustomerWallet($customer->id, 'SOL', 'SOL');
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

    public function wallets() { return $this->hasMany(Wallet::class); }

    public function findOrCreateWallet($currency = 'BTC')
    {
        return $this->wallets()->firstOrCreate(['currency' => $currency]);
    }

    public function getWallet($currency)
    {
        return $this->wallets()->firstOrCreate(['currency' => $currency], ['balance' => 0]);
    }

    public function getDefaultWallet()
    {
        return $this->wallets()->where('is_default', true)->first() ?? $this->findOrCreateWallet('BTC');
    }

    public function getWalletAddress($currency)
    {
        $wallet = $this->getWallet($currency);
        return [
            'address' => $wallet->address,
            'deposit_tag' => $wallet->deposit_tag,
        ];
    }

    public function getWalletBalance($currency)
    {
        $wallet = $this->getWallet($currency);
        return [
            'balance' => $wallet->balance,
        ];
    }

    public function getWalletQrCode($currency)
    {
        $wallet = $this->getWallet($currency);
        return [
            'qr_code' => QrCode::size(250)->generate($wallet->address),
        ];
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hasSufficientWalletBalance($amount): bool
    {
        $wallet = $this->getDefaultWallet();
        return $wallet && $wallet->balance >= $amount;
    }

    public function deductFromWallet($amount): bool
    {
        $wallet = $this->getDefaultWallet();

        if (!$wallet || $wallet->balance < $amount) {
            return false;
        }

        $wallet->balance -= $amount;
        $wallet->save();

        $wallet->transactions()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $this->id,
            'amount' => -$amount,
            'type' => 'debit',
            'status' => 'completed',
            'reference' => 'wallet_' . strtoupper(Str::random(8)),
            'description' => 'Order payment',
        ]);

        return true;
    }

    public function addToWallet($amount, $description = 'Top-up'): void
    {
        $wallet = $this->getDefaultWallet();

        $wallet->balance += $amount;
        $wallet->save();

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
}
