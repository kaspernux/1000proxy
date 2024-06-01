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
        'country',
        'flag',
        'ip_address',
        'panel_url',
        'port',
        'username',
        'password',
        'description',
        'status',
    ];

    public function inbounds(): HasMany
    {
        return $this->hasMany(ServerInbound::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class, 'server_category_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class);
    }

    public function config(): HasOne
    {
        return $this->hasOne(ServerConfig::class);
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