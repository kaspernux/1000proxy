<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasStaffRoles
{
    /**
     * Check if user has a specific staff role
     */
    public function hasStaffRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the specified staff roles
     */
    public function hasAnyStaffRole(array $roles): bool
    {
        return in_array($this->role, $roles);
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
     * Check if user has administrative privileges
     */
    public function hasAdministrativePrivileges(): bool
    {
        return in_array($this->role, ['admin', 'support_manager']);
    }

    /**
     * Check if user can manage customers
     */
    public function canManageCustomers(): bool
    {
        return in_array($this->role, ['admin', 'support_manager']);
    }

    /**
     * Check if user can manage servers
     */
    public function canManageServers(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports(): bool
    {
        return in_array($this->role, ['admin', 'support_manager']);
    }

    /**
     * Check if user can manage staff
     */
    public function canManageStaff(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'support_manager' => 'Support Manager',
            'sales_support' => 'Sales Support',
            default => ucfirst(str_replace('_', ' ', $this->role))
        };
    }

    /**
     * Get role badge color for UI
     */
    public function getRoleBadgeColor(): string
    {
        return match($this->role) {
            'admin' => 'danger',
            'support_manager' => 'warning',
            'sales_support' => 'info',
            default => 'gray'
        };
    }

    /**
     * Get role icon for UI
     */
    public function getRoleIcon(): string
    {
        return match($this->role) {
            'admin' => 'heroicon-o-shield-check',
            'support_manager' => 'heroicon-o-user-group',
            'sales_support' => 'heroicon-o-phone',
            default => 'heroicon-o-user'
        };
    }

    /**
     * Scope query to specific staff roles
     */
    public function scopeWithStaffRole(Builder $query, string|array $roles): Builder
    {
        if (is_string($roles)) {
            return $query->where('role', $roles);
        }

        return $query->whereIn('role', $roles);
    }

    /**
     * Scope query to administrators only
     */
    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope query to support staff only
     */
    public function scopeSupportStaff(Builder $query): Builder
    {
        return $query->whereIn('role', ['support_manager', 'sales_support']);
    }

    /**
     * Scope query to active staff only
     */
    public function scopeActiveStaff(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all available staff roles
     */
    public static function getStaffRoles(): array
    {
        return [
            'admin' => 'Administrator',
            'support_manager' => 'Support Manager',
            'sales_support' => 'Sales Support',
        ];
    }

    /**
     * Get staff role permissions
     */
    public function getStaffPermissions(): array
    {
        return match($this->role) {
            'admin' => [
                'manage_staff',
                'manage_customers',
                'manage_servers',
                'manage_orders',
                'view_reports',
                'manage_settings',
                'access_telegram_bot',
                'manage_payments',
                'export_data',
            ],
            'support_manager' => [
                'manage_customers',
                'view_orders',
                'view_reports',
                'access_telegram_bot',
                'customer_support',
                'export_customer_data',
            ],
            'sales_support' => [
                'view_customers',
                'view_orders',
                'customer_support',
                'access_telegram_bot',
            ],
            default => []
        };
    }

    /**
     * Check if user has specific permission
     */
    public function hasStaffPermission(string $permission): bool
    {
        return in_array($permission, $this->getStaffPermissions());
    }
}
