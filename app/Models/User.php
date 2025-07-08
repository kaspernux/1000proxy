<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Sanctum\HasApiTokens;

use Filament\Panel;



class User extends Authenticatable implements FilamentUser
    {
    use HasFactory, Notifiable, HasFactory, HasApiTokens;

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
        // Secure admin access - only specific roles or admin emails
        return $this->hasRole('admin') || 
               str_ends_with($this->email, '@admin.com') || 
               in_array($this->email, ['admin@1000proxy.com', 'support@1000proxy.com']);
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
}
