<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'title',
        'parent',
        'description',
        'step',
        'active',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}