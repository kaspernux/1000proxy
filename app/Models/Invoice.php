<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'iid',
        'wallet_transaction_id',
        'customer_id',
        'payment_method_id',
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
        'invoice_url',
        'success_url',
        'cancel_url',
        'partially_paid_url',
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
        'redirect_url',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function serverPlan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class, 'server_plan_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}