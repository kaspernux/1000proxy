<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'image',
        'is_active',
    ];


    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function serverPlans()
    {
        return $this->hasMany(ServerPlan::class, 'server_category_id');
    }

    public function serverClients()
    {
        return $this->hasMany(ServerClient::class);
    }
}