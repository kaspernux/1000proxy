<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SetupStaffRoles extends Command
{
    protected $signature = 'staff:setup
                           {--force : Force setup even if users exist}
                           {--admin-email=admin@1000proxy.io : Admin email}
                           {--admin-password=admin123!@# : Admin password}';

    protected $description = 'Setup staff roles and create default admin users';

    public function handle()
    {
        $this->info('Setting up staff roles for 1000proxy...');

        // Check if users table has required columns
        if (!$this->checkDatabaseSchema()) {
            $this->error('Database schema is not ready. Please run migrations first.');
            return 1;
        }

        // Create default staff users
        $this->createDefaultStaffUsers();

        // Update existing users if any
        $this->updateExistingUsers();

        $this->info('Staff roles setup completed successfully!');
        $this->displayStaffAccounts();

        return 0;
    }

    private function checkDatabaseSchema(): bool
    {
        $requiredColumns = ['role', 'is_active', 'last_login_at', 'telegram_chat_id'];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('users', $column)) {
                $this->warn("Missing column: {$column}");
                return false;
            }
        }

        return true;
    }

    private function createDefaultStaffUsers(): void
    {
        $adminEmail = $this->option('admin-email');
        $adminPassword = $this->option('admin-password');

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->info("✓ Created admin user: {$adminEmail}");
        } else {
            $this->warn("Admin user already exists: {$adminEmail}");
        }

        // Create support manager (if it doesn't exist)
        $supportManager = User::firstOrCreate(
            ['email' => 'support@1000proxy.io'],
            [
                'name' => 'Support Manager',
                'username' => 'support_manager',
                'email' => 'support@1000proxy.io',
                'password' => Hash::make('support123!@#'),
                'role' => 'support_manager',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($supportManager->wasRecentlyCreated) {
            $this->info("✓ Created support manager: support@1000proxy.io");
        }

        // Create sales support (if it doesn't exist)
        $salesSupport = User::firstOrCreate(
            ['email' => 'sales@1000proxy.io'],
            [
                'name' => 'Sales Support',
                'username' => 'sales_support',
                'email' => 'sales@1000proxy.io',
                'password' => Hash::make('sales123!@#'),
                'role' => 'sales_support',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($salesSupport->wasRecentlyCreated) {
            $this->info("✓ Created sales support: sales@1000proxy.io");
        }
    }

    private function updateExistingUsers(): void
    {
        // Update users without roles
        $usersWithoutRole = User::whereNull('role')->orWhere('role', '')->get();

        if ($usersWithoutRole->count() > 0) {
            $this->info("Found {$usersWithoutRole->count()} users without roles. Updating...");

            foreach ($usersWithoutRole as $user) {
                $user->update([
                    'role' => 'support_manager', // Default role for existing users
                    'is_active' => true,
                ]);
                $this->info("✓ Updated user: {$user->email} (set as support_manager)");
            }
        }

        // Update users with old role system
        $usersWithOldRoles = User::whereNotIn('role', ['admin', 'support_manager', 'sales_support'])->get();

        if ($usersWithOldRoles->count() > 0) {
            $this->info("Found {$usersWithOldRoles->count()} users with old roles. Updating...");

            foreach ($usersWithOldRoles as $user) {
                $newRole = match($user->role) {
                    'user', 'customer' => 'support_manager',
                    'moderator', 'manager' => 'support_manager',
                    'administrator', 'superadmin' => 'admin',
                    default => 'support_manager'
                };

                $user->update(['role' => $newRole]);
                $this->info("✓ Updated user: {$user->email} ({$user->role} → {$newRole})");
            }
        }
    }

    private function displayStaffAccounts(): void
    {
        $this->newLine();
        $this->info('=== Staff Accounts Summary ===');

        $staffUsers = User::whereIn('role', ['admin', 'support_manager', 'sales_support'])
                         ->orderBy('role')
                         ->get();

        $headers = ['Name', 'Email', 'Role', 'Status', 'Last Login'];
        $rows = [];

        foreach ($staffUsers as $user) {
            $rows[] = [
                $user->name,
                $user->email,
                $user->getRoleDisplayName(),
                $user->is_active ? '✓ Active' : '✗ Inactive',
                $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('M d, Y H:i') : 'Never'
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Default passwords:');
        $this->line('Admin: ' . $this->option('admin-password'));
        $this->line('Support Manager: support123!@#');
        $this->line('Sales Support: sales123!@#');
        $this->newLine();
        $this->warn('Please change default passwords after first login!');
    }
}
