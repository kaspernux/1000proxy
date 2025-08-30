<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostalCode extends Model
{
    protected $fillable = ['city_id','country_id','postal_code'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
