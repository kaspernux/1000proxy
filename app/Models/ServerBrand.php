<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServerBrand extends Model
{
    use HasFactory;

    protected $table = 'server_brands';

    protected $fillable = [
        'name',
        'slug',
        'image',
    'description',
        'desc',
        'is_active',
        'website_url',
        'support_url',
        'tier',
        'brand_color',
        'featured',
        'sort_order',
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

    // Backward/forward compatibility for description vs desc
    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['description'] ?? $this->attributes['desc'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value;
        // Keep legacy column in sync if present
        $this->attributes['desc'] = $value;
    }

    public function serverCategories(): HasMany
    {
        return $this->hasMany(ServerCategory::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ServerPlan::class, 'server_brand_id');
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'server_brand_id');
    }
}
