<?php

namespace App\Models;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';


    protected $fillable = [
        'customer_id',
        'grand_amount',
        'currency',
        'payment_method',
        'payment_status',
        'order_status',
        'payment_invoice_url',
        'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /* public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    } */

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

}