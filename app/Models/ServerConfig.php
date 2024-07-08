<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerConfig extends Model
{
    use HasFactory;

    protected $table = 'server_configs';

    protected $fillable = [
        'server_id',
        'panel_url',
        'ip',
        'port',
        'sni',
        'header_type',
        'request_header',
        'response_header',
        'security',
        'tlsSettings',
        'type',
        'username',
        'password',
        'port_type',
        'reality',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
}