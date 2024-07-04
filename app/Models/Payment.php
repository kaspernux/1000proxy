<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'customer_id',
        'payment_id',
        'payment_status',
        'pay_address',
        'price_amount',
        'price_currency',
        'pay_amount',
        'pay_currency',
        'order_id',
        'order_description',
        'ipn_callback_url',
        'created_at',
        'updated_at',
        'purchase_id',
        'amount_received',
        'payin_extra_id',
        'smart_contract',
        'network',
        'network_precision',
        'time_limit',
        'expiration_estimate_date',
        'is_fixed_rate',
        'is_fee_paid_by_user',
        'valid_until',
        'type',
        'redirect_url'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
