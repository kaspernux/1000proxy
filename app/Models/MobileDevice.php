<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class MobileDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'device_identifier',
        'device_name',
        'device_type',
        'platform',
        'platform_version',
        'app_version',
        'push_token',
        'push_notifications_enabled',
        'timezone',
        'language',
        'is_active',
        'last_seen_at',
        'last_sync_at',
        'offline_data_size',
        'sync_version',
        'sync_status'
    ];

    protected $casts = [
        'push_notifications_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'sync_version' => 'integer',
        'offline_data_size' => 'integer'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MobileSession::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(PushNotification::class);
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for devices with push notifications enabled
     */
    public function scopePushEnabled($query)
    {
        return $query->where('push_notifications_enabled', true);
    }

    /**
     * Check if device is online (last seen within 5 minutes)
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at && $this->last_seen_at > now()->subMinutes(5);
    }

    /**
     * Get device platform icon
     */
    public function getPlatformIconAttribute(): string
    {
        return match(strtolower($this->platform)) {
            'android' => '🤖',
            'ios' => '📱',
            'web' => '🌐',
            default => '📱'
        };
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Update sync information
     */
    public function updateSyncInfo(array $syncData): void
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_version' => ($this->sync_version ?? 0) + 1,
            'sync_status' => $syncData['status'] ?? 'completed',
            'offline_data_size' => $syncData['data_size'] ?? $this->offline_data_size
        ]);
    }
}
