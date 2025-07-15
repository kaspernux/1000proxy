<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Enhanced User Management System
 *
 * Provides advanced user management capabilities including:
 * - Advanced filtering and search
 * - Bulk operations
 * - User communication tools
 * - Activity monitoring
 * - Role management
 */
class EnhancedUserManagementService
{
    /**
     * Advanced user search with multiple filters
     */
    public function searchUsers(array $filters = []): Builder
    {
        $query = User::query();

        // Text search across multiple fields
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telegram_username', 'like', "%{$search}%");
            });
        }

        // Role filtering
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Status filtering
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Telegram integration status
        if (isset($filters['has_telegram'])) {
            if ($filters['has_telegram']) {
                $query->whereNotNull('telegram_user_id');
            } else {
                $query->whereNull('telegram_user_id');
            }
        }

        // Registration date range
        if (!empty($filters['registered_from'])) {
            $query->where('created_at', '>=', $filters['registered_from']);
        }
        if (!empty($filters['registered_to'])) {
            $query->where('created_at', '<=', $filters['registered_to']);
        }

        // Last activity filtering
        if (!empty($filters['last_active_days'])) {
            $days = (int) $filters['last_active_days'];
            $query->where('last_login_at', '>=', now()->subDays($days));
        }

        // Inactive users (never logged in or long inactive)
        if (!empty($filters['inactive_period'])) {
            $days = (int) $filters['inactive_period'];
            $query->where(function ($q) use ($days) {
                $q->whereNull('last_login_at')
                  ->orWhere('last_login_at', '<', now()->subDays($days));
            });
        }

        return $query;
    }

    /**
     * Get user statistics and analytics
     */
    public function getUserStatistics(): array
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'telegram_linked' => User::whereNotNull('telegram_user_id')->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'support_users' => User::where('role', 'support_manager')->count(),
            'sales_users' => User::where('role', 'sales_support')->count(),
        ];

        // Registration trends (last 30 days)
        $stats['daily_registrations'] = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Activity trends
        $stats['daily_logins'] = User::selectRaw('DATE(last_login_at) as date, COUNT(*) as count')
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Role distribution
        $stats['role_distribution'] = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get()
            ->pluck('count', 'role')
            ->toArray();

        return $stats;
    }

    /**
     * Bulk user operations
     */
    public function bulkActivateUsers(array $userIds): array
    {
        try {
            $updated = User::whereIn('id', $userIds)->update([
                'is_active' => true,
                'updated_at' => now()
            ]);

            Log::info('Bulk user activation', [
                'user_ids' => $userIds,
                'count' => $updated,
                'performed_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Successfully activated {$updated} users",
                'count' => $updated
            ];
        } catch (\Exception $e) {
            Log::error('Bulk user activation failed', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to activate users: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    public function bulkDeactivateUsers(array $userIds): array
    {
        try {
            $updated = User::whereIn('id', $userIds)->update([
                'is_active' => false,
                'updated_at' => now()
            ]);

            Log::info('Bulk user deactivation', [
                'user_ids' => $userIds,
                'count' => $updated,
                'performed_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Successfully deactivated {$updated} users",
                'count' => $updated
            ];
        } catch (\Exception $e) {
            Log::error('Bulk user deactivation failed', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to deactivate users: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    public function bulkChangeRole(array $userIds, string $newRole): array
    {
        try {
            // Validate role
            $validRoles = ['admin', 'support_manager', 'sales_support'];
            if (!in_array($newRole, $validRoles)) {
                throw new \InvalidArgumentException("Invalid role: {$newRole}");
            }

            $updated = User::whereIn('id', $userIds)->update([
                'role' => $newRole,
                'updated_at' => now()
            ]);

            Log::info('Bulk role change', [
                'user_ids' => $userIds,
                'new_role' => $newRole,
                'count' => $updated,
                'performed_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Successfully changed role to {$newRole} for {$updated} users",
                'count' => $updated
            ];
        } catch (\Exception $e) {
            Log::error('Bulk role change failed', [
                'user_ids' => $userIds,
                'new_role' => $newRole,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to change user roles: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    public function bulkPasswordReset(array $userIds): array
    {
        try {
            $users = User::whereIn('id', $userIds)->get();
            $resetCount = 0;

            foreach ($users as $user) {
                // Generate password reset token
                $token = app('auth.password.broker')->createToken($user);

                // Send password reset email
                $user->sendPasswordResetNotification($token);
                $resetCount++;
            }

            Log::info('Bulk password reset', [
                'user_ids' => $userIds,
                'count' => $resetCount,
                'performed_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Successfully sent password reset emails to {$resetCount} users",
                'count' => $resetCount
            ];
        } catch (\Exception $e) {
            Log::error('Bulk password reset failed', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send password reset emails: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * User communication tools
     */
    public function sendBulkNotification(array $userIds, string $subject, string $message, string $type = 'info'): array
    {
        try {
            $users = User::whereIn('id', $userIds)->get();
            $sentCount = 0;

            foreach ($users as $user) {
                // Send email notification
                Mail::send('emails.bulk-notification', [
                    'user' => $user,
                    'subject' => $subject,
                    'message' => $message,
                    'type' => $type
                ], function ($m) use ($user, $subject) {
                    $m->to($user->email, $user->name)->subject($subject);
                });

                // Send Telegram notification if available
                if ($user->telegram_user_id && app()->bound('telegram')) {
                    try {
                        app('telegram')->sendMessage([
                            'chat_id' => $user->telegram_user_id,
                            'text' => "📢 {$subject}\n\n{$message}",
                            'parse_mode' => 'HTML'
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to send Telegram notification', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $sentCount++;
            }

            Log::info('Bulk notification sent', [
                'user_ids' => $userIds,
                'subject' => $subject,
                'count' => $sentCount,
                'performed_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => "Successfully sent notifications to {$sentCount} users",
                'count' => $sentCount
            ];
        } catch (\Exception $e) {
            Log::error('Bulk notification failed', [
                'user_ids' => $userIds,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notifications: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * User activity monitoring
     */
    public function getDetailedUserActivity(int $userId): array
    {
        $user = User::findOrFail($userId);

        $activity = [
            'user' => $user,
            'login_history' => $this->getLoginHistory($userId),
            'session_info' => $this->getSessionInfo($userId),
            'recent_activity' => $this->getRecentActivity($userId),
            'security_events' => $this->getSecurityEvents($userId),
        ];

        return $activity;
    }

    protected function getLoginHistory(int $userId): array
    {
        // This would require a login_logs table to track login history
        // For now, return basic info from user record
        $user = User::find($userId);

        return [
            'last_login' => $user->last_login_at,
            'login_count' => $user->login_count ?? 0,
            'registration_date' => $user->created_at,
        ];
    }

    protected function getSessionInfo(int $userId): array
    {
        // Get active sessions for user
        return [
            'active_sessions' => 1, // This would require session tracking
            'current_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
    }

    protected function getRecentActivity(int $userId): array
    {
        // This would require an activity log system
        return [
            'recent_actions' => [],
            'page_views' => [],
            'api_calls' => [],
        ];
    }

    protected function getSecurityEvents(int $userId): array
    {
        // This would require security event logging
        return [
            'failed_logins' => [],
            'password_changes' => [],
            'role_changes' => [],
        ];
    }

    /**
     * Export user data
     */
    public function exportUsers(array $filters = [], string $format = 'csv'): string
    {
        $users = $this->searchUsers($filters)->get();

        $data = $users->map(function ($user) {
            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->role,
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Telegram' => $user->telegram_username ?? 'Not linked',
                'Last Login' => $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
                'Registered' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });

        if ($format === 'csv') {
            return $this->exportToCsv($data);
        }

        return $this->exportToJson($data);
    }

    protected function exportToCsv($data): string
    {
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Add headers
        if ($data->isNotEmpty()) {
            fputcsv($file, array_keys($data->first()));
        }

        // Add data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return $filepath;
    }

    protected function exportToJson($data): string
    {
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));

        return $filepath;
    }
}
