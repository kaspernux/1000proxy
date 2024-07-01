<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'payment_method_id',
        'order_id',
        'order_description',
        'price_amount',
        'price_currency',
        'pay_currency',
        'ipn_callback_url',
        'invoice_url',
        'success_url',
        'cancel_url',
        'partially_paid_url',
        'is_fixed_rate',
        'is_fee_paid_by_user',
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
}
