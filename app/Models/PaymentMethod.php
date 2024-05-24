<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'customer_id',
        'name',           // Name of the payment method (e.g., Visa, MasterCard, PayPal, Bitcoin, Ethereum)
        'details',        // Additional details related to the payment method (e.g., card number, PayPal email, crypto wallet address)
        'is_default',
        'expiration_date',
        'billing_address',
        'type',           // Type of payment method (e.g., Credit Card, Crypto)
        'is_active',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}