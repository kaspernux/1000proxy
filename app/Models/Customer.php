<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Customer extends Model
    {
    use HasFactory, Notifiable;

    protected $table = 'customers';

    protected $fillable = [

        'image',
        'email',
        'password',
        'telegram_id',
        'is_active',
    ];

    public function orders(): HasMany
        {
        return $this->hasMany(Order::class);
        }

    public function invoices(): HasMany
        {
        return $this->hasMany(Invoice::class);
        }

    public function serverReviews(): HasMany
        {
        return $this->hasMany(ServerReview::class);
        }

    public function serverRatings(): HasMany
        {
        return $this->hasMany(ServerRating::class);
        }
    }
