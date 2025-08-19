<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Wallet;

class WalletTransaction extends Model {
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'type',
        'amount',
        'status',
        'reference',
        'address', 
        'payment_id',
        'metadata',
        'description',
        'qr_code_path',
        'gateway',
        'currency',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = (string) Str::uuid();
            }
        });

        static::updated(function (WalletTransaction $transaction) {
            if (
                $transaction->type === 'deposit' &&
                in_array($transaction->status, ['completed', 'confirmed']) &&
                $transaction->wasChanged('status') &&
                (
                    !isset($transaction->metadata['order_created']) ||
                    $transaction->metadata['order_created'] === false
                )
            ) {
                dispatch(function () use ($transaction) {
                    if (!isset($transaction->metadata['order_created']) || $transaction->metadata['order_created'] === false) {
                        $transaction->customer?->autoCreateOrderFromDeposit($transaction);
                    } else {
                        \Log::info("🛑 Duplicate auto-order attempt prevented", ['tx' => $transaction->reference]);
                    }
                });
            }
        });
    }

    /**
     * Get the wallet associated with the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the customer associated with the transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if the transaction was successful.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['completed', 'confirmed']);
    }
}
