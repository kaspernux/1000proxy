<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
    {
    protected $table = 'payment_methods';

    protected $fillable = [
        'user_id',
        'name',
        'details',
        'is_default',
    ];

    public function user(): BelongsTo
        {
        return $this->belongsTo(User::class, 'user_id');
        }
    }