<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ServerInfo;
use App\Models\Server;
use App\Models\Order;
use App\Models\Customer;

class DownloadableItem extends Model
{
    protected $table = 'downloadable_items';

    protected $fillable = [
        // Core fields
        'server_id',
        'file_url',
        'name',
        'description',

        // Download controls
        'download_limit',
        'current_downloads',
        'expiration_time',

        // Access controls
        'access_type',
        'is_active',
        'require_authentication',
        'track_downloads',

        // Security
        'download_token',
        'checksum',
        'allowed_ips',

        // File metadata
        'file_size',
        'mime_type',
        'version',
        'changelog',
        'category',
    ];

    protected $casts = [
        'expiration_time' => 'datetime',
        'is_active' => 'boolean',
        'require_authentication' => 'boolean',
        'track_downloads' => 'boolean',
        'allowed_ips' => 'array',
        'current_downloads' => 'integer',
        'download_limit' => 'integer',
        'file_size' => 'integer',
    ];

    // Relationships
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    // Utility methods
    public function isExpired(): bool
    {
        return $this->expiration_time && $this->expiration_time <= now();
    }

    public function isAvailable(): bool
    {
        return $this->is_active && !$this->isExpired() && !$this->hasReachedDownloadLimit();
    }

    public function hasReachedDownloadLimit(): bool
    {
        return $this->download_limit > 0 && $this->current_downloads >= $this->download_limit;
    }

    public function getRemainingDownloads(): int
    {
        if ($this->download_limit == 0) {
            return -1; // Unlimited
        }
        return max(0, $this->download_limit - $this->current_downloads);
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('current_downloads');
    }

    public function generateDownloadToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['download_token' => $token]);
        return $token;
    }

    public function calculateChecksum(): string
    {
        if ($this->file_url && file_exists(storage_path('app/public/' . $this->file_url))) {
            $checksum = md5_file(storage_path('app/public/' . $this->file_url));
            $this->update(['checksum' => $checksum]);
            return $checksum;
        }
        return '';
    }
}

