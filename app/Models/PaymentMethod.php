<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'image',
        'name',
        'type',
        'notes',
        'is_active',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /* public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    } */

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}