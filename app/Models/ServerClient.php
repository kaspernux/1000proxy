<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerClient extends Model
{
    use HasFactory;

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
        'subId',
        'enable',
        'reset',
        'qr_code_sub',
        'qr_code_sub_json',
        'qr_code_client',
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class);
    }
}