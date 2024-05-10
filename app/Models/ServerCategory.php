<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerCategory extends Model
    {
    protected $fillable = [
        'name', 'description', 'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function servers()
    {
        return $this->hasMany(Server::class);
    }
    }
