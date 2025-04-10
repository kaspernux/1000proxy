<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ServerInbound;
use App\Models\ServerPlan;

class ServerClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'server_clients';

    protected $fillable = [
        'server_inbound_id',
        'email',
        'password',
        'flow',
        'limitIp',
        'totalGb',
        'expiryTime',
        'tgId',
        'subId',
        'plan_id',
        'enable',
        'reset',
        'qr_code_sub',
        'qr_code_sub_json',
        'qr_code_client',
    ];

    protected $casts = [
        'totalGb' => 'integer',
        'expiryTime' => 'datetime',
        'enable' => 'boolean',
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class, 'plan_id');
    }

    /**
     * Create or update a ServerClient from a remote client payload.
     *
     * @param array $client
     * @param int $inboundId
     * @return ServerClient
     */
    public static function fromRemoteClient(array $client, int $inboundId): self
    {
        // Optional email-based deduplication logic
        if (!empty($client['email'])) {
            $existing = self::where('email', $client['email'])->first();
            if ($existing && $existing->subId !== ($client['subId'] ?? null)) {
                // Same email but different client → return existing to avoid overwrite
                return $existing;
            }
        }

        return self::updateOrCreate(
            [
                'subId' => $client['subId'] ?? null,
            ],
            [
                'server_inbound_id' => $inboundId,
                'email' => $client['email'] ?? null,
                'password' => $client['id'] ?? null,
                'flow' => $client['flow'] ?? null,
                'limitIp' => $client['limitIp'] ?? 0,
                'totalGb' => isset($client['totalGB']) ? floor($client['totalGB'] / 1073741824) : 0,
                'expiryTime' => isset($client['expiryTime']) && $client['expiryTime'] > 0
                    ? Carbon::createFromTimestampMs($client['expiryTime'])
                    : null,
                'tgId' => $client['tgId'] ?? null,
                'enable' => $client['enable'] ?? true,
                'reset' => $client['reset'] ?? null,
                'qr_code_client' => $client['qrCode-0'] ?? null,
                'qr_code_sub' => $client['qrCode-sub'] ?? null,
                'qr_code_sub_json' => $client['qrCode-subJson'] ?? null,
            ]
        );
    }
}
