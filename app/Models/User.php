<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;

    
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasStaffRoles;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;
use App\Traits\LogsActivity;



class User extends Authenticatable implements FilamentUser
    {
    use HasFactory, Notifiable, HasFactory, HasApiTokens, HasStaffRoles, HasRoles, LogsActivity;

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
        'locale',
        'phone',
        'theme_mode',
        'email_notifications',
        'timezone',
    'two_factor_enabled',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'notification_preferences',
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
            'two_factor_enabled' => 'boolean',
            'notification_preferences' => 'array',
        ];
        }

    public function canAccessPanel(?Panel $panel = null): bool
    {
        // Accept optional panel for backward compatibility with older tests
        if (!$this->is_active) {
            return false;
        }

        // Allow explicit roles
    if (in_array($this->role, ['admin', 'manager', 'support_manager', 'sales_support', 'analyst'])) {
            return true;
        }

        // Allow specific admin emails (legacy behavior referenced in tests & docs)
        if (in_array($this->email, [
            'admin@1000proxy.io',
            'support@1000proxy.io',
        ])) {
            return true;
        }

        return false;
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
            'manager' => ' (Manager)',
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
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
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
     * Check if user is an analyst
     */
    public function isAnalyst(): bool
    {
        return $this->role === 'analyst';
    }

    /**
     * Get available user roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'support_manager' => 'Support Manager',
            'sales_support' => 'Sales Support',
            'analyst' => 'Analyst',
        ];
    }

    /**
     * Locale helpers
     */
    public function setLocale(string $locale): void
    {
        if ($this->locale !== $locale) {
            $this->locale = $locale;
            $this->saveQuietly();
        }
    }

    public function getLocaleOrDefault(): string
    {
        return $this->locale ?: config('locales.default', 'en');
    }

    /**
     * Get all activity logs for the user.
     */
    public function userActivities()
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Log an activity and update last_activity_at.
     */
    public function logActivity($action, $description = null, $ip = null)
    {
        $this->userActivities()->create([
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip ?? request()->ip(),
        ]);
        $this->last_activity_at = now();
        $this->save();
    }

    // Staff do not own orders; access via filtered queries or services.
}