<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServerPlan extends Model
{
    use HasFactory;

    protected $table = 'server_plans';

    protected $fillable = [
        'server_category_id',
        'server_brand_id',
        'name',
        'slug',
        'product_image',
        'description',
        'capacity',
        'price',
        'type',
        'days',
        'volume',
        'is_active',
        'is_featured',
        'in_stock',
        'on_sale',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ServerBrand::class, 'server_brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class, 'server_category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
