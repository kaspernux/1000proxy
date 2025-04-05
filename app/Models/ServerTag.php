<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerTag extends Model
    {
    use HasFactory;
    protected $table = 'server_tags';

    protected $fillable = [
        'name',
        'server_id',
    ];

    public function server(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }
    }