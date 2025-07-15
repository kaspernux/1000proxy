<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasStaffRoles;

use Filament\Panel;



class User extends Authenticatable implements FilamentUser
    {
    use HasFactory, Notifiable, HasFactory, HasApiTokens, HasStaffRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'telegram_chat_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telegram_chat_id',
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
        {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
        }

    public function canAccessPanel(Panel $panel): bool
    {
        // Allow users with admin, support_manager, or sales_support roles to access admin panel
        return in_array($this->role, ['admin', 'support_manager', 'sales_support']) && $this->is_active;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has Telegram linked
     */
    public function hasTelegramLinked(): bool
    {
        return !empty($this->telegram_chat_id);
    }

    /**
     * Get Telegram display name
     */
    public function getTelegramDisplayName(): string
    {
        if ($this->telegram_first_name && $this->telegram_last_name) {
            return $this->telegram_first_name . ' ' . $this->telegram_last_name;
        }

        return $this->telegram_first_name ?: $this->telegram_username ?: 'Unknown';
    }

    /**
     * Link Telegram account
     */
    public function linkTelegram(int $chatId, ?string $username = null, ?string $firstName = null, ?string $lastName = null): void
    {
        $this->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_first_name' => $firstName,
            'telegram_last_name' => $lastName,
        ]);
    }

    /**
     * Unlink Telegram account
     */
    public function unlinkTelegram(): void
    {
        $this->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_first_name' => null,
            'telegram_last_name' => null,
        ]);
    }

    /**
     * SCOPES
     */

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive users
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for users with Telegram linked
     */
    public function scopeWithTelegram($query)
    {
        return $query->whereNotNull('telegram_chat_id');
    }

    /**
     * Scope for users without Telegram
     */
    public function scopeWithoutTelegram($query)
    {
        return $query->whereNull('telegram_chat_id');
    }

    /**
     * Scope for administrators
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for support managers
     */
    public function scopeSupportManagers($query)
    {
        return $query->where('role', 'support_manager');
    }

    /**
     * Scope for sales support
     */
    public function scopeSalesSupport($query)
    {
        return $query->where('role', 'sales_support');
    }

    /**
     * Scope for recent logins (last 30 days)
     */
    public function scopeRecentLogins($query)
    {
        return $query->where('last_login_at', '>=', now()->subDays(30));
    }

    /**
     * ACCESSORS & MUTATORS
     */

    /**
     * Get user's full name with role indicator
     */
    public function getDisplayNameAttribute(): string
    {
        $roleMap = [
            'admin' => ' (Admin)',
            'support_manager' => ' (Support Manager)',
            'sales_support' => ' (Sales Support)',
        ];

        $roleIndicator = $roleMap[$this->role] ?? '';
        return $this->name . $roleIndicator;
    }

    /**
     * Get user's registration age in days
     */
    public function getRegistrationAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if user is an administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a support manager
     */
    public function isSupportManager(): bool
    {
        return $this->role === 'support_manager';
    }

    /**
     * Check if user is sales support
     */
    public function isSalesSupport(): bool
    {
        return $this->role === 'sales_support';
    }

    /**
     * Get available user roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            'admin' => 'Administrator',
            'support_manager' => 'Support Manager',
            'sales_support' => 'Sales Support',
        ];
    }
}
