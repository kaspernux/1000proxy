<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';


    protected $fillable = [
        'customer_id',
        'grand_amount',
        'currency',
        'payment_status',
        'order_status',
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