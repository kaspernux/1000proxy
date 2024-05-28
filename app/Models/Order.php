<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'grand_amount',
        'currency',
        'payment_method_id',
        'transaction_id',
        'payment_status',
        'order_status',
        'order_date',
        'notes',
    ];

    // Define the relationships with other models
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethod(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'payment_method_id');
    }

    // Optionally define additional relationships if needed

    // Optionally define accessors, mutators, or other methods as required
}
