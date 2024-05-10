<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerClient extends Model
    {
    protected $fillable = [
        'id',
        'up',
        'down',
        'total',
        'remark',
        'enable',
        'expiryTime',
        'listen',
        'port',
        'protocol',
        'settings',
        'streamSettings',
        'tag',
        'sniffing',
    ];

    protected $casts = [
        'enable' => 'boolean', // Cast 'enable' to boolean
        'expiryTime' => 'datetime', // Cast 'expiryTime' to datetime
        'clientStats' => 'array', // Cast 'clientStats' to array
        'settings' => 'array', // Cast 'settings' to array
        'streamSettings' => 'array', // Cast 'streamSettings' to array
        'sniffing' => 'array', // Cast 'sniffing' to array
    ];

    // Define the relationship with the Inbound model
    public function serverInbound(): BelongsTo
        {
        return $this->belongsTo(ServerInbound::class);
        }

    public function cartItems(): HasMany
        {
        return $this->hasMany(CartItem::class);
        }

    public function DownloadableItems(): HasMany
        {
        return $this->hasMany(DownloadableItem::class);
        }

    }