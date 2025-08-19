<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MobileSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'device_id',
        'session_token',
        'ip_address',
        'user_agent',
        'expires_at',
        'is_active',
        'last_activity_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Check if session is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if session is valid
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }

    /**
     * Update last activity
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Invalidate session
     */
    public function invalidate(): void
    {
        $this->update(['is_active' => false]);
    }
}
