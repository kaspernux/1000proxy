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
        'server_id',
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

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Example method to manage plans
    public function managePlan()
    {
        $xuiService = app(XUIService::class);
        // Use $this->server_id to fetch server-specific details
        $server = $this->server;

        // Example: Fetch plans
        $plans = $xuiService->fetchPlans($server->getXUIParameters());

        // Example: Process plans
        foreach ($plans as $plan) {
            // Process each plan as needed
        }
    }

}