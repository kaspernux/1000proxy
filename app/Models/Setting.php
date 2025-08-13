<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;
    protected $table = 'site_settings';

    protected $fillable = ['key', 'value', 'description'];

    protected $casts = [
        'value' => 'string',
    ];
}
