<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
    {
    use HasFactory;
    protected $table = 'invoices';
    protected $casts = [
        'invoice_date' => 'datetime',
    ];

    protected $fillable = [
        'order_id',
        'invoice_date',
        'total_amount',
    ];

    public function order(): BelongsTo
        {
        return $this->belongsTo(Order::class);
        }

    public function customer(): BelongsTo
        {
        return $this->belongsTo(Customer::class);
        }
    public function serverPlan(): BelongsTo
        {
        return $this->belongsTo(ServerPlan::class)->withPivot('inbound_id', 'price');
        }


    }