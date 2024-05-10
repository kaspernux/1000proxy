<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerInbound extends Model
    {
    protected $fillable = [
        'up',
        'down',
        'total',
        'remark',
        'enable',
        'expiryTime',
        'clientStats',
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
        'sniffing' => 'array', //
    ];
    public function serverClients(): HasMany
        {
        return $this->hasMany(ServerClient::class);
        }
    public function serverCategories(): HasMany
        {
        return $this->hasMany(ServerCategory::class);
        }
    }
