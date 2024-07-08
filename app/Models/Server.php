<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Server extends Model
{
    use HasFactory;

    protected $table = 'servers';

    protected $fillable = [
        'name',
        'server_category_id',
        'server_brand_id',
        'country',
        'flag',
        'description',
        'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class, 'server_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ServerBrand::class, 'server_brand_id');
    }

    public function config(): hasOne
    {
        return $this->hasOne(ServerConfig::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ServerPlan::class);
    }

    public function giftLists(): HasMany
    {
        return $this->hasMany(GiftList::class);
    }

    public function serverReviews(): HasMany
    {
        return $this->hasMany(ServerReview::class);
    }

    public function serverRatings(): HasMany
    {
        return $this->hasMany(ServerRating::class, 'server_id');
    }

    public function averageRating(): float
    {
        return $this->serverRatings()->avg('rating') ?? 0.0;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function serverInfo(): HasOne
    {
        return $this->hasOne(ServerInfo::class);
    }
}