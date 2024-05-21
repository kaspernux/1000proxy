<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
    {
    protected $table = 'payment_methods';

    protected $fillable = [
        'customer_id',
        'name',           // Name of the payment method (e.g., Visa, MasterCard, PayPal)
        'details',        // Additional details related to the payment method (e.g., card number, PayPal email)
        'is_default',
        'expiration_date',
        'billing_address',
        'type',
        'is_active',
    ];

     public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function orders(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
        {
        return $this->hasMany(Payments::class);
        }
    }
