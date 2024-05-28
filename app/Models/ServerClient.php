<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerClient extends Model
{
    protected $fillable = [
        'server_category_id',
        'brand_id',
        'server_inbound_id',
        'name',
        'slug',
        'email',
        'images',
        'description',
        'price',
        'is_active',
        'is_featured',
        'in_stock',
        'enable',
        'up',
        'down',
        'expiry_time',
        'total',
        'reset',
    ];

    protected $casts = [
        'enable' => 'boolean', // Cast 'enable' to boolean
        'expiry_time' => 'datetime', // Cast 'expiryTime' to datetime
        'images' => 'array',
    ];

    public function serverInbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function serverCategory(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class);
    }

    public function brands(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}