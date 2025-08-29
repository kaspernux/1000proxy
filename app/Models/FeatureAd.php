<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureAd extends Model
{
    use HasFactory;

    protected $table = 'feature_ads';

    protected $fillable = [
        'server_id', 'title', 'subtitle', 'body', 'metadata', 'is_active', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
}
