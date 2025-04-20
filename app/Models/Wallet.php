<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str; 
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'currency',
        'balance',
        'address',
        'deposit_tag',
        'network',
        'qr_code_path',
        'last_synced_at',
        'is_default',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'is_default' => 'boolean',
    ];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function transactions() {
        return $this->hasMany(WalletTransaction::class);
    }

    public function deposit($amount, $reference, $metadata = []) {
        $transaction = $this->transactions()->create([
            'customer_id' => $this->customer_id, // ✅ add this line
            'wallet_id' => $this->id, // (optional since it's implied, but safe)
            'type' => 'deposit',
            'amount' => $amount,
            'status' => 'completed',
            'reference' => $reference,
            'metadata' => $metadata,
        ]);

        $this->increment('balance', $amount);

        return $transaction;
    }


    public function withdraw($amount, $reference, $metadata = []) {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }

        $transaction = $this->transactions()->create([
            'customer_id' => $this->customer_id, // ✅
            'wallet_id' => $this->id,
            'type' => 'withdrawal',
            'amount' => -abs($amount),
            'status' => 'completed',
            'reference' => $reference,
            'metadata' => $metadata,
        ]);

        $this->decrement('balance', $amount);

        return $transaction;
    }

    /**
     * Generate a unique deposit address and optional tag/memo based on currency.
     */
    public static function generateDepositAddress($currency)
    {
        switch (strtoupper($currency)) {
            case 'BTC':
                return [
                    'address' => 'btc_' . Str::random(32), // Integrate with actual BTC wallet API
                    'deposit_tag' => null,
                ];
            case 'XMR':
                return [
                    'address' => 'xmr_' . Str::random(64), // Integrate with actual XMR wallet RPC
                    'deposit_tag' => Str::random(16),      // Payment ID or Integrated address
                ];
            case 'SOL':
                return [
                    'address' => 'sol_' . Str::random(44), // Integrate with actual Solana wallet API
                    'deposit_tag' => null,
                ];
            default:
                throw new \Exception('Unsupported currency');
        }
    }

    /**
     * Convenience method to create wallet for customer with generated address.
     */
    public static function createCustomerWallet($customerId, $currency, $network = null, $isDefault = false)
    {
        $addressInfo = self::generateDepositAddress($currency);

        $wallet = self::create([
            'customer_id' => $customerId,
            'currency' => strtoupper($currency),
            'network' => $network ?? strtoupper($currency),
            'address' => $addressInfo['address'],
            'deposit_tag' => $addressInfo['deposit_tag'],
            'is_default' => $isDefault,
        ]);

        $wallet->generateQrCode();

        return $wallet;
    }

    /**
     * Generate and store QR code for the wallet deposit address.
     */
    public function generateQrCode()
    {
        $qrData = $this->address;
        if ($this->deposit_tag) {
            $qrData .= '?memo=' . $this->deposit_tag;
        }

        $filename = 'wallet_qr_' . $this->id . '.png';
        $path = storage_path('app/public/wallet_qr/' . $filename);

        QrCode::format('png')->size(300)->generate($qrData, $path);

        $this->update(['qr_code_path' => 'wallet_qr/' . $filename]);
    }

    /**
     * Synchronize wallet balance with external blockchain services.
     */
    public function syncWithBlockchain()
    {
        // Example pseudo-code, replace with real API integration
        $balance = match ($this->currency) {
            'BTC' => $this->fetchBtcBalance(),
            'XMR' => $this->fetchXmrBalance(),
            'SOL' => $this->fetchSolBalance(),
            default => throw new \Exception('Unsupported currency'),
        };

        $this->update([
            'balance' => $balance,
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'last_synced_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);
    }


    // Example implementations (pseudo-code)
    protected function fetchBtcBalance()
    {
        // Integrate with Bitcoin API provider
        return 0.0;
    }

    protected function fetchXmrBalance()
    {
        // Integrate with Monero RPC API
        return 0.0;
    }

    protected function fetchSolBalance()
    {
        // Integrate with Solana JSON-RPC API
        return 0.0;
    }
}
