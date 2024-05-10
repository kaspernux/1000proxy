<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ServerConfig extends Model
    {
    protected $table = 'server_configs';

    protected $fillable = [
        'panel_url',
        'ip',
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
        'server_id',
    ];

    public function server(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }
    }