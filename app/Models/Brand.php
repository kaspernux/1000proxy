<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable =[
        'name',
        'slug',
        'image',
        'is_active',
    ];

    public function serverClients()
        {
            return $this->hasMany(ServerClient::class);
        }

    public function serverPlans()
    {
        return $this->hasMany(ServerPlan::class);
    }
}