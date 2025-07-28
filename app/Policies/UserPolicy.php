<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any staff members.
     */
    public function viewAny(User $user): bool
    {
        // All staff roles can view the UserResource
        return in_array($user->role, ['admin', 'support_manager', 'sales_support']);
    }

    /**
     * Determine whether the user can view the staff member.
     */
    public function view(User $user, User $model): bool
    {
        // All staff roles can view all staff users
        return in_array($user->role, ['admin', 'support_manager', 'sales_support']);
    }

    /**
     * Determine whether the user can create staff members.
     */
    public function create(User $user): bool
    {
        // Only admin can create staff users
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the staff member.
     */
    public function update(User $user, User $model): bool
    {
        // Only admin can update staff users
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the staff member.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admin can delete staff members, and they can't delete themselves
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the staff member.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the staff member.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can manage staff roles.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Only admin can change roles, and they can't change their own role to non-admin
        if (!$user->isAdmin()) {
            return false;
        }

        // Admin can't demote themselves
        if ($user->id === $model->id && $model->role === 'admin') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can reset passwords for other staff.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->isAdmin() || ($user->isSupportManager() && !$model->isAdmin());
    }

    /**
     * Determine whether the user can activate/deactivate staff accounts.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Only admin can toggle status, and they can't deactivate themselves
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can send notifications to staff.
     */
    public function sendNotifications(User $user): bool
    {
        return $user->hasStaffPermission('manage_staff') || $user->isSupportManager();
    }

    /**
     * Determine whether the user can export staff data.
     */
    public function exportData(User $user): bool
    {
        return $user->hasStaffPermission('export_data');
    }

    /**
     * Determine whether the user can manage Telegram integration for staff.
     */
    public function manageTelegram(User $user, User $model): bool
    {
        // Users can manage their own Telegram, admin can manage all
        return $user->isAdmin() || $user->id === $model->id;
    }
}
