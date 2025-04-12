<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerInbound extends Model
{
    use HasFactory;

    protected $table = 'server_inbounds';

    protected $fillable = [
        'server_id',
        'protocol',
        'port',
        'enable',
        'remark',
        'expiryTime',
        'settings',
        'streamSettings',
        'sniffing',
        'up',
        'down',
        'total',
        'allocate',
    ];

    protected $casts = [
        'up' => 'integer',
        'down' => 'integer',
        'total' => 'integer',
        'enable' => 'boolean',
        'expiryTime' => 'datetime',
        'clientStats' => 'array',
        'settings' => 'array',
        'streamSettings' => 'array',
        'sniffing' => 'array',
        'allocate' => 'array',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class, 'server_inbound_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    /**
     * Create or update a ServerInbound from a remote inbound object.
     *
     * @param object $inbound
     * @param int $serverId
     * @return ServerInbound
     */
    public static function fromRemoteInbound(object $inbound, int $serverId): self
    {
        $settings = json_decode($inbound->settings ?? '{}', true);
        $clients = $settings['clients'] ?? [];

        return self::updateOrCreate(
            [
                'server_id' => $serverId,
                'port' => $inbound->port,
            ],
            [
                'protocol' => $inbound->protocol ?? null,
                'remark' => $inbound->remark ?? '',
                'enable' => $inbound->enable ?? true,
                'expiryTime' => isset($inbound->expiryTime)
                    ? Carbon::createFromTimestampMs($inbound->expiryTime)
                    : null,
                'settings' => $settings,
                'streamSettings' => is_string($inbound->streamSettings) ? json_decode($inbound->streamSettings, true) : (array) $inbound->streamSettings,
                'sniffing' => json_decode($inbound->sniffing ?? '{}', true),
                'allocate' => json_decode($inbound->allocate ?? '{}', true),
                'up' => $inbound->up ?? 0,
                'down' => $inbound->down ?? 0,
                'total' => count($clients), // ✅ set total to number of clients
            ]
        );
    }

}
