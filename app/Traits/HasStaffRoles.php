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
     * Check if user has administrative privileges
     */
    public function hasAdministrativePrivileges(): bool
    {
    return in_array($this->role, ['admin', 'manager', 'support_manager']);
    }

    /**
     * Check if user can manage customers
     */
    public function canManageCustomers(): bool
    {
    return in_array($this->role, ['admin', 'manager', 'support_manager']);
    }

    /**
     * Check if user can manage servers
     */
    public function canManageServers(): bool
    {
    // Managers can manage existing servers (view/update/delete) but cannot create new ones
    return in_array($this->role, ['admin', 'manager']);
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports(): bool
    {
    return in_array($this->role, ['admin', 'manager', 'support_manager', 'analyst']);
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
            'manager' => 'Manager',
            'support_manager' => 'Support Manager',
            'sales_support' => 'Sales Support',
            'analyst' => 'Analyst',
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
            'manager' => 'primary',
            'support_manager' => 'warning',
            'sales_support' => 'info',
            'analyst' => 'success',
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
            'manager' => 'heroicon-o-briefcase',
            'support_manager' => 'heroicon-o-user-group',
            'sales_support' => 'heroicon-o-phone',
            'analyst' => 'heroicon-o-chart-bar',
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
            'manager' => 'Manager',
            'support_manager' => 'Support Manager',
            'sales_support' => 'Sales Support',
            'analyst' => 'Analyst',
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
                'manage_server_plans',
                'view_reports',
                'manage_settings',
                'access_telegram_bot',
                'manage_payments',
                'export_data',
            ],
            'manager' => [
                'manage_customers',
                'manage_orders',
                'manage_server_plans',
                // Can manage existing servers (view/update/delete), creation stays admin-only via policy
                'view_reports',
                'access_telegram_bot',
                'export_data',
            ],
            'support_manager' => [
                'manage_customers',
                'view_orders', // read-only order access
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
            'analyst' => [
                'view_reports',
                'export_data',
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
