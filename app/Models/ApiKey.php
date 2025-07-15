<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'user_id',
        'is_active',
        'expires_at',
        'last_used_at',
        'permissions'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'permissions' => 'array'
    ];

    /**
     * Generate a new API key
     */
    public static function generateKey(): string
    {
        return Str::random(32);
    }

    /**
     * Create a new API key
     */
    public static function createForUser($user, string $name, array $permissions = []): self
    {
        return self::create([
            'name' => $name,
            'key' => self::generateKey(),
            'user_id' => $user->id,
            'is_active' => true,
            'expires_at' => now()->addYear(),
            'permissions' => $permissions
        ]);
    }

    /**
     * Check if API key is valid
     */
    public function isValid(): bool
    {
        return $this->is_active &&
               (!$this->expires_at || $this->expires_at > now());
    }

    /**
     * Mark API key as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get the user that owns the API key
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
