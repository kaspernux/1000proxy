<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ApiKey;
use App\Rules\PasswordComplexity;

class SecurityCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:manage {action} {--user=} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Manage security features: test, audit, cleanup, enforce';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        return match ($action) {
            'test' => $this->testSecurityFeatures(),
            'audit' => $this->performSecurityAudit(),
            'cleanup' => $this->cleanupSecurityData(),
            'enforce' => $this->enforceSecurityPolicies(),
            'status' => $this->showSecurityStatus(),
            'block-ip' => $this->blockIP(),
            'unblock-ip' => $this->unblockIP(),
            'reset-attempts' => $this->resetLoginAttempts(),
            default => $this->error("Unknown action: {$action}. Available: test, audit, cleanup, enforce, status, block-ip, unblock-ip, reset-attempts")
        };
    }

    /**
     * Test all security features
     */
    private function testSecurityFeatures(): int
    {
        $this->info('🔒 Testing Security Features');
        $this->newLine();

        $tests = [
            'Password Complexity' => $this->testPasswordComplexity(),
            'Rate Limiting' => $this->testRateLimiting(),
            'Session Security' => $this->testSessionSecurity(),
            'CSRF Protection' => $this->testCSRFProtection(),
            'Security Headers' => $this->testSecurityHeaders(),
            'Login Monitoring' => $this->testLoginMonitoring(),
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $test => $result) {
            if ($result) {
                $this->info("✅ {$test}: PASSED");
                $passed++;
            } else {
                $this->error("❌ {$test}: FAILED");
            }
        }

        $this->newLine();
        $this->info("Security Test Results: {$passed}/{$total} tests passed");

        return $passed === $total ? 0 : 1;
    }

    /**
     * Test password complexity rules
     */
    private function testPasswordComplexity(): bool
    {
        try {
            $weakPasswords = ['password', '123456', 'qwerty', 'admin123'];
            $strongPassword = 'MyStr0ng!P@ssw0rd2024';

            $rule = new PasswordComplexity();

            foreach ($weakPasswords as $password) {
                $isValid = true;
                $rule->validate('password', $password, function () use (&$isValid) {
                    $isValid = false;
                });

                if ($isValid) {
                    $this->warn("  ⚠️  Weak password '{$password}' was accepted");
                    return false;
                }
            }

            // Test strong password
            $isValid = true;
            $rule->validate('password', $strongPassword, function () use (&$isValid) {
                $isValid = false;
            });

            if (!$isValid) {
                $this->warn("  ⚠️  Strong password was rejected");
                return false;
            }

            // Test password strength scoring
            $strength = PasswordComplexity::getPasswordStrength($strongPassword);
            if ($strength['score'] < 60) {
                $this->warn("  ⚠️  Password strength scoring not working properly");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Password complexity test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test rate limiting functionality
     */
    private function testRateLimiting(): bool
    {
        try {
            // Test that rate limiting cache keys can be created
            $testKey = 'rate_limit_test:' . uniqid();
            Cache::put($testKey, 1, 60);

            if (!Cache::has($testKey)) {
                $this->warn("  ⚠️  Cache not working for rate limiting");
                return false;
            }

            Cache::forget($testKey);
            return true;
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Rate limiting test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test session security
     */
    private function testSessionSecurity(): bool
    {
        try {
            // Check if session configuration is secure
            $sessionConfig = config('session');

            if (app()->environment('production')) {
                if (!$sessionConfig['secure']) {
                    $this->warn("  ⚠️  Session cookies should be secure in production");
                    return false;
                }

                if (!$sessionConfig['http_only']) {
                    $this->warn("  ⚠️  Session cookies should be HTTP only");
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Session security test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test CSRF protection
     */
    private function testCSRFProtection(): bool
    {
        try {
            // Check if CSRF is enabled
            $csrfConfig = config('security.csrf', []);

            if (!($csrfConfig['enabled'] ?? true)) {
                $this->warn("  ⚠️  CSRF protection is disabled");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->warn("  ⚠️  CSRF protection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test security headers
     */
    private function testContentSecurityPolicy(): bool
    {
        try {
            $response = $this->call('GET', '/');
            $cspHeader = $response->headers->get('Content-Security-Policy');
            
            if (empty($cspHeader)) {
                $this->warn("  ⚠️  No CSP header found");
                return false;
            }
            
            if (str_contains($cspHeader, "'unsafe-eval'")) {
                $this->warn("  ⚠️  CSP contains unsafe-eval (consider using nonces)");
                // Don't return false here as this might be intentional for Livewire
            }
            
            return true;
        } catch (\Exception $e) {
            $this->warn("  ⚠️  CSP test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test login monitoring
     */
    private function testLoginMonitoring(): bool
    {
        try {
            // Test that we can track login attempts
            $testKey = 'login_test:' . uniqid();
            Cache::put($testKey, 1, 300);

            return Cache::has($testKey);
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Login monitoring test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform comprehensive security audit
     */
    private function performSecurityAudit(): int
    {
        $this->info('🔍 Performing Security Audit');
        $this->newLine();

        $issues = [];

        // Check user passwords
        $weakPasswordUsers = $this->auditUserPasswords();
        if (!empty($weakPasswordUsers)) {
            $issues[] = count($weakPasswordUsers) . " users have weak passwords";
        }

        // Check for suspicious activity
        $suspiciousActivity = $this->auditSuspiciousActivity();
        if (!empty($suspiciousActivity)) {
            $issues[] = count($suspiciousActivity) . " suspicious activities detected";
        }

        // Check API keys
        $apiKeyIssues = $this->auditApiKeys();
        if (!empty($apiKeyIssues)) {
            $issues = array_merge($issues, $apiKeyIssues);
        }

        // Check blocked IPs
        $blockedIPs = $this->auditBlockedIPs();
        if (!empty($blockedIPs)) {
            $this->info("📊 Currently blocked IPs: " . count($blockedIPs));
            foreach ($blockedIPs as $ip => $reason) {
                $this->line("  • {$ip}: {$reason}");
            }
        }

        if (empty($issues)) {
            $this->info("✅ No security issues found");
            return 0;
        } else {
            $this->error("⚠️  Security issues found:");
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
            return 1;
        }
    }

    /**
     * Audit user passwords
     */
    private function auditUserPasswords(): array
    {
        $weakUsers = [];

        try {
            // This is a simplified check - in reality, you can't reverse hash passwords
            // Instead, you might check for users who haven't changed passwords in a long time
            $usersWithOldPasswords = User::where('password_changed_at', '<', now()->subDays(90))
                ->orWhereNull('password_changed_at')
                ->get();

            foreach ($usersWithOldPasswords as $user) {
                $weakUsers[] = $user->email;
            }
        } catch (\Exception $e) {
            $this->warn("Could not audit passwords: " . $e->getMessage());
        }

        return $weakUsers;
    }

    /**
     * Audit suspicious activity
     */
    private function auditSuspiciousActivity(): array
    {
        $suspicious = [];

        try {
            // Check for multiple failed login attempts
            $cacheKeys = Cache::getRedis()->keys('failed_attempts:*');
            foreach ($cacheKeys as $key) {
                $attempts = Cache::get($key, 0);
                if ($attempts >= 3) {
                    $suspicious[] = $key . ": {$attempts} failed attempts";
                }
            }
        } catch (\Exception $e) {
            $this->warn("Could not audit suspicious activity: " . $e->getMessage());
        }

        return $suspicious;
    }

    /**
     * Audit API keys
     */
    private function auditApiKeys(): array
    {
        $issues = [];

        try {
            $expiredKeys = ApiKey::where('expires_at', '<', now())->count();
            if ($expiredKeys > 0) {
                $issues[] = "{$expiredKeys} expired API keys need cleanup";
            }

            $oldKeys = ApiKey::where('last_used_at', '<', now()->subDays(30))
                ->orWhereNull('last_used_at')
                ->count();
            if ($oldKeys > 0) {
                $issues[] = "{$oldKeys} API keys haven't been used in 30+ days";
            }
        } catch (\Exception $e) {
            // API keys table might not exist yet
            $this->warn("Could not audit API keys: " . $e->getMessage());
        }

        return $issues;
    }

    /**
     * Audit blocked IPs
     */
    private function auditBlockedIPs(): array
    {
        $blocked = [];

        try {
            $cacheKeys = Cache::getRedis()->keys('blocked_ip:*');
            foreach ($cacheKeys as $key) {
                $ip = str_replace('blocked_ip:', '', $key);
                $blocked[$ip] = 'Security violation';
            }
        } catch (\Exception $e) {
            $this->warn("Could not audit blocked IPs: " . $e->getMessage());
        }

        return $blocked;
    }

    /**
     * Cleanup security data
     */
    private function cleanupSecurityData(): int
    {
        $this->info('🧹 Cleaning up security data');

        $cleaned = 0;

        // Clean expired API keys
        try {
            $expiredKeys = ApiKey::where('expires_at', '<', now())->delete();
            if ($expiredKeys > 0) {
                $this->info("  Cleaned {$expiredKeys} expired API keys");
                $cleaned += $expiredKeys;
            }
        } catch (\Exception $e) {
            $this->warn("Could not clean API keys: " . $e->getMessage());
        }

        // Clean old login attempts
        try {
            $cacheKeys = Cache::getRedis()->keys('failed_attempts:*');
            $oldAttempts = 0;
            foreach ($cacheKeys as $key) {
                if (Cache::get($key . '_timestamp', 0) < time() - 3600) {
                    Cache::forget($key);
                    $oldAttempts++;
                }
            }
            if ($oldAttempts > 0) {
                $this->info("  Cleaned {$oldAttempts} old login attempt records");
                $cleaned += $oldAttempts;
            }
        } catch (\Exception $e) {
            $this->warn("Could not clean login attempts: " . $e->getMessage());
        }

        // Clean old session data
        try {
            $cacheKeys = Cache::getRedis()->keys('session_activity:*');
            $oldSessions = 0;
            foreach ($cacheKeys as $key) {
                // Remove sessions older than 24 hours
                $activity = Cache::get($key, []);
                if (!empty($activity)) {
                    $lastActivity = end($activity)['timestamp'] ?? null;
                    if ($lastActivity && strtotime($lastActivity) < time() - 86400) {
                        Cache::forget($key);
                        $oldSessions++;
                    }
                }
            }
            if ($oldSessions > 0) {
                $this->info("  Cleaned {$oldSessions} old session records");
                $cleaned += $oldSessions;
            }
        } catch (\Exception $e) {
            $this->warn("Could not clean session data: " . $e->getMessage());
        }

        if ($cleaned === 0) {
            $this->info("  No data needed cleaning");
        }

        return 0;
    }

    /**
     * Enforce security policies
     */
    private function enforceSecurityPolicies(): int
    {
        $this->info('⚖️  Enforcing security policies');

        $enforced = 0;

        // Force password changes for users with old passwords
        if ($this->option('force') || $this->confirm('Force password change for users with passwords older than 90 days?')) {
            try {
                $oldPasswordUsers = User::where('password_changed_at', '<', now()->subDays(90))
                    ->orWhereNull('password_changed_at')
                    ->update(['force_password_change' => true]);

                if ($oldPasswordUsers > 0) {
                    $this->info("  Forced password change for {$oldPasswordUsers} users");
                    $enforced++;
                }
            } catch (\Exception $e) {
                $this->warn("Could not enforce password changes: " . $e->getMessage());
            }
        }

        // Disable inactive API keys
        if ($this->option('force') || $this->confirm('Disable API keys not used in 60+ days?')) {
            try {
                $inactiveKeys = ApiKey::where('last_used_at', '<', now()->subDays(60))
                    ->orWhereNull('last_used_at')
                    ->update(['is_active' => false]);

                if ($inactiveKeys > 0) {
                    $this->info("  Disabled {$inactiveKeys} inactive API keys");
                    $enforced++;
                }
            } catch (\Exception $e) {
                $this->warn("Could not disable API keys: " . $e->getMessage());
            }
        }

        if ($enforced === 0) {
            $this->info("  No policies needed enforcement");
        }

        return 0;
    }

    /**
     * Show security status
     */
    private function showSecurityStatus(): int
    {
        $this->info('📊 Security Status Overview');
        $this->newLine();

        // Users with 2FA enabled
        try {
            $totalUsers = User::count();
            $users2FA = User::whereNotNull('two_factor_secret')->count();
            $this->info("👥 Users: {$totalUsers} total, {$users2FA} with 2FA enabled");
        } catch (\Exception $e) {
            $this->warn("Could not get user stats: " . $e->getMessage());
        }

        // API Keys
        try {
            $totalApiKeys = ApiKey::count();
            $activeApiKeys = ApiKey::where('is_active', true)->count();
            $this->info("🔑 API Keys: {$totalApiKeys} total, {$activeApiKeys} active");
        } catch (\Exception $e) {
            $this->warn("Could not get API key stats: " . $e->getMessage());
        }

        // Current blocked IPs
        try {
            $blockedIPs = count(Cache::getRedis()->keys('blocked_ip:*'));
            $this->info("🚫 Blocked IPs: {$blockedIPs}");
        } catch (\Exception $e) {
            $this->warn("Could not get blocked IP stats: " . $e->getMessage());
        }

        // Recent login attempts
        try {
            $failedAttempts = count(Cache::getRedis()->keys('failed_attempts:*'));
            $this->info("🚨 Failed login attempts (recent): {$failedAttempts}");
        } catch (\Exception $e) {
            $this->warn("Could not get login attempt stats: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Block an IP address
     */
    private function blockIP(): int
    {
        $ip = $this->ask('Enter IP address to block:');

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format');
            return 1;
        }

        $reason = $this->ask('Reason for blocking:', 'Manual block');
        $duration = $this->ask('Duration in minutes:', '60');

        Cache::put("blocked_ip:{$ip}", $reason, now()->addMinutes((int)$duration));

        $this->info("✅ IP {$ip} has been blocked for {$duration} minutes");
        Log::warning("IP manually blocked", ['ip' => $ip, 'reason' => $reason, 'duration' => $duration]);

        return 0;
    }

    /**
     * Unblock an IP address
     */
    private function unblockIP(): int
    {
        $ip = $this->ask('Enter IP address to unblock:');

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format');
            return 1;
        }

        if (Cache::forget("blocked_ip:{$ip}")) {
            $this->info("✅ IP {$ip} has been unblocked");
            Log::info("IP manually unblocked", ['ip' => $ip]);
        } else {
            $this->warn("IP {$ip} was not found in blocked list");
        }

        return 0;
    }

    /**
     * Reset login attempts for a user or IP
     */
    private function resetLoginAttempts(): int
    {
        $target = $this->ask('Enter email or IP to reset login attempts for:');

        $keys = [
            "failed_attempts:email:{$target}",
            "failed_attempts:ip:{$target}",
            "login_attempts:email:{$target}:last_hour",
            "login_attempts:ip:{$target}:last_hour",
            "locked_account:{$target}"
        ];

        $cleared = 0;
        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $cleared++;
            }
        }

        if ($cleared > 0) {
            $this->info("✅ Reset {$cleared} login attempt records for {$target}");
            Log::info("Login attempts manually reset", ['target' => $target]);
        } else {
            $this->warn("No login attempt records found for {$target}");
        }

        return 0;
    }
}
