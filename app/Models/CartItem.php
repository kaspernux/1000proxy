<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CartItem extends Model
    {
    protected $table = 'cart_items';

    protected $fillable = [
        'user_id',
        'server_client_id',
        'price',
    ];

    public function serverClient(): BelongsTo
        {
        return $this->belongsTo(ServerClient::class);
        }
    }