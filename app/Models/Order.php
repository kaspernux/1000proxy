<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
    {
    use HasFactory;
    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'order_date',
        'total_amount',
        'payment_status',
        'order_status',
    ];

    public function customer(): BelongsTo
        {
        return $this->belongsTo(Customer::class);
        }
    }
