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
        'balance',
        'btc_address',
        'xmr_address',
        'sol_address',
        'btc_qr',
        'xmr_qr',
        'sol_qr',
        'last_synced_at',
        'is_default',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'is_default' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ✨ Deposit
    public function deposit($amount, $reference, $metadata = [])
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'wallet_id' => $this->id,
            'customer_id' => $this->customer_id,
            'type' => 'deposit',
            'amount' => $amount,
            'status' => 'completed',
            'reference' => $reference,
            'metadata' => $metadata,
        ]);
    }

    // ✨ Withdraw
    public function withdraw($amount, $reference, $metadata = [])
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'wallet_id' => $this->id,
            'customer_id' => $this->customer_id,
            'type' => 'withdrawal',
            'amount' => -abs($amount),
            'status' => 'completed',
            'reference' => $reference,
            'metadata' => $metadata,
        ]);
    }

    // ✨ Generate crypto deposit addresses and QRs dynamically
    public function generateDepositAddresses()
    {
        $this->btc_address = 'btc_' . Str::random(34);
        $this->xmr_address = 'xmr_' . Str::random(64);
        $this->sol_address = 'sol_' . Str::random(44);
        $this->save();

        $this->generateQrCodes();
    }

    // ✨ Generate QR Codes for the wallet addresses
    public function generateQrCodes()
    {
        $this->btc_qr = $this->generateQrFor($this->btc_address, 'btc');
        $this->xmr_qr = $this->generateQrFor($this->xmr_address, 'xmr');
        $this->sol_qr = $this->generateQrFor($this->sol_address, 'sol');
        $this->save();
    }

    protected function generateQrFor($address, $type)
    {
        try {
            $qrService = app(\App\Services\QrCodeService::class);
            $filename = 'wallet_qr_' . $this->id . '_' . $type . '.png';
            $path = storage_path('app/public/wallet_qr/' . $filename);

            // Ensure directory exists
            $directory = dirname($path);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Generate QR code using our service with fallback handling
            $qrData = $qrService->generateBrandedQrCode($address, 300, 'png', [
                'colorScheme' => 'primary',
                'style' => 'square', // Use simpler style for wallet QRs
                'eye' => 'square'
            ]);

            file_put_contents($path, $qrData);

            return 'wallet_qr/' . $filename;
        } catch (\Exception $e) {
            // If QR generation fails, return a placeholder path
            \Log::warning("Failed to generate QR code for wallet {$this->id}, type {$type}: " . $e->getMessage());
            return 'wallet_qr/placeholder_' . $type . '.png';
        }
    }
}
