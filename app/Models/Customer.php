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
        if ($wallet->balance < $amount) {
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

}
