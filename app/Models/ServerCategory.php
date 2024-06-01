<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServerCategory extends Model
{
    use HasFactory;

    protected $table = 'server_categories';

    protected $fillable = [
        'name',
        'slug',
        'image',
        'is_active',
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

    public function plans(): HasMany
    {
        return $this->hasMany(ServerPlan::class, 'server_category_id');
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'server_category_id');
    }
}