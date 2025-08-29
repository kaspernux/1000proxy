<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class StaffManagementService
{
    /**
     * Return a base query for staff users with optional filters applied.
     */
    public function searchUsers(array $filters = []): Builder
    {
        $query = User::with('userActivities')
            ->whereIn('role', ['admin', 'manager', 'analyst', 'support_manager', 'sales_support'])
            ->orderByDesc('last_activity_at');

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telegram_username', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (int) $filters['is_active']);
        }

        if (isset($filters['has_telegram']) && $filters['has_telegram'] !== '' && $filters['has_telegram'] !== null) {
            if ((int) $filters['has_telegram'] === 1) {
                $query->whereNotNull('telegram_chat_id');
            } else {
                $query->whereNull('telegram_chat_id');
            }
        }

        if (!empty($filters['last_active_days'])) {
            $days = (int) $filters['last_active_days'];
            $query->whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDays($days));
        }

        return $query;
    }

    /**
     * Aggregate simple statistics for staff users.
     */
    public function getUserStatistics(): array
    {
        $baseQuery = User::query()->whereIn('role', ['admin', 'manager', 'analyst', 'support_manager', 'sales_support']);

        $total = (clone $baseQuery)->count();
        $active = (clone $baseQuery)->where('is_active', 1)->count();
        $admins = (clone $baseQuery)->where('role', 'admin')->count();
        $withTelegram = (clone $baseQuery)->whereNotNull('telegram_chat_id')->count();

        // Role distribution
        $roleDistribution = (clone $baseQuery)
            ->selectRaw('role, COUNT(*) as cnt')
            ->groupBy('role')
            ->pluck('cnt', 'role')
            ->toArray();

        // Daily registrations / logins (last 30 days)
        $start = now()->subDays(29)->startOfDay();
        $registrations = [];
        $logins = [];
        for ($i = 0; $i < 30; $i++) {
            $day = (clone $start)->copy()->addDays($i);
            $key = $day->format('Y-m-d');
            $registrations[$key] = (clone $baseQuery)->whereDate('created_at', $key)->count();
            $logins[$key] = (clone $baseQuery)->whereDate('last_login_at', $key)->count();
        }

        return [
            'total_users' => $total,
            'active_users' => $active,
            'admin_users' => $admins,
            'telegram_linked' => $withTelegram,
            'role_distribution' => $roleDistribution,
            'daily_registrations' => $registrations,
            'daily_logins' => $logins,
        ];
    }

    public function bulkActivateUsers(array $ids): array
    {
        $affected = User::query()
            ->whereIn('id', $ids)
            ->whereIn('role', ['admin', 'manager', 'analyst', 'support_manager', 'sales_support'])
            ->update(['is_active' => 1]);

        return [
            'success' => true,
            'message' => "Activated {$affected} users.",
        ];
    }

    public function bulkDeactivateUsers(array $ids): array
    {
        $affected = User::query()
            ->whereIn('id', $ids)
            ->whereIn('role', ['admin', 'manager', 'analyst', 'support_manager', 'sales_support'])
            ->update(['is_active' => 0]);

        return [
            'success' => true,
            'message' => "Deactivated {$affected} users.",
        ];
    }

    public function bulkChangeRole(array $ids, string $role): array
    {
        $allowed = ['admin', 'manager', 'analyst', 'support_manager', 'sales_support'];
        if (!in_array($role, $allowed, true)) {
            return [
                'success' => false,
                'message' => 'Invalid role.',
            ];
        }

        $affected = User::query()
            ->whereIn('id', $ids)
            ->update(['role' => $role]);

        return [
            'success' => true,
            'message' => "Changed role for {$affected} users to {$role}.",
        ];
    }

    public function bulkPasswordReset(array $ids): array
    {
        $users = User::query()->whereIn('id', $ids)->get(['email']);
        $sent = 0; $failed = 0;
        foreach ($users as $user) {
            $status = Password::sendResetLink(['email' => $user->email]);
            if ($status === Password::RESET_LINK_SENT) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => true,
            'message' => "Password reset links: sent={$sent}, failed={$failed}.",
        ];
    }

    public function sendBulkNotification(array $userIds, string $subject, string $message, string $type = 'info'): array
    {
        // Placeholder: integrate with your notification/broadcast system as needed.
        $count = User::whereIn('id', $userIds)->count();
        Log::info('Bulk notification dispatched', [
            'recipients' => $count,
            'subject' => $subject,
            'type' => $type,
        ]);

        return [
            'success' => true,
            'message' => "Notification queued for {$count} users.",
        ];
    }

    public function exportUsers(array $filters = [], string $format = 'csv'): string
    {
        $query = $this->searchUsers($filters);
        $users = $query->get([
            'id', 'name', 'email', 'role', 'is_active', 'telegram_chat_id', 'last_login_at', 'created_at',
        ]);

        $dir = storage_path('app/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $dir . '/staff-users-' . now()->format('Ymd_His') . '.csv';
        $fp = fopen($filename, 'w');
        // Header
        fputcsv($fp, ['ID', 'Name', 'Email', 'Role', 'Active', 'Telegram Linked', 'Last Login', 'Registered']);
        foreach ($users as $u) {
            fputcsv($fp, [
                $u->id,
                $u->name,
                $u->email,
                $u->role,
                $u->is_active ? 'Yes' : 'No',
                $u->telegram_chat_id ? 'Yes' : 'No',
                optional($u->last_login_at)->toDateTimeString(),
                optional($u->created_at)->toDateTimeString(),
            ]);
        }
        fclose($fp);

        return $filename;
    }
}
