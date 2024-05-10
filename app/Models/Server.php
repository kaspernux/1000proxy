<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Server extends Model
    {
    use HasFactory;
    protected $table = 'servers';

    protected $fillable = [
        'ip',
        'port',
        'username',
        'password',
        'name',
        'panel',
        'status',
    ];
    public function serverCategory(): BelongsTo
        {
        return $this->belongsTo(ServerCategory::class);
        }

    public function serverInbounds(): HasMany
        {
        return $this->hasMany(ServerInbound::class);
        }

    public function serverClients(): HasMany
        {
        return $this->hasMany(ServerClient::class);
        }

    public function serverConfigs(): HasMany
        {
        return $this->hasMany(ServerConfig::class);
        }

    public function serverPlans(): HasMany
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
        return $this->hasMany(ServerRating::class);
        }

    }