<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class DownloadableItem extends Model
    {

    protected $fillable = [
        'server_client_id',
        'file_url',
        'download_limit',
        'expiration_time',
    ];

    public function serverClient(): BelongsTo
        {
        return $this->belongsTo(ServerClient::class);
        }
    }