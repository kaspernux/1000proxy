<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Services\AdvancedProxyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use Carbon\Carbon;

/**
 * IP Rotation Scheduler Service
 *
 * Handles automated IP rotation based on various triggers and schedules.
 */
class IPRotationScheduler
{
    protected $advancedProxyService;

    public function __construct(AdvancedProxyService $advancedProxyService)
    {
        $this->advancedProxyService = $advancedProxyService;
    }

    /**
     * Execute scheduled IP rotations
     */
    public function executeScheduledRotations(): array
    {
        try {
            $rotationConfigs = $this->getActiveRotationConfigs();
            $executedRotations = [];

            foreach ($rotationConfigs as $config) {
                if ($this->shouldExecuteRotation($config)) {
                    $result = $this->executeRotation($config);
                    $executedRotations[] = $result;

                    // Update next rotation time
                    $this->updateNextRotationTime($config);
                }
            }

            Log::info('Scheduled IP rotations executed', [
                'total_configs' => count($rotationConfigs),
                'executed_rotations' => count($executedRotations)
            ]);

            return [
                'success' => true,
                'executed_rotations' => $executedRotations,
                'total_configs_checked' => count($rotationConfigs)
            ];
        } catch (\Exception $e) {
            Log::error('Scheduled IP rotation execution error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if rotation should be executed based on configuration
     */
    private function shouldExecuteRotation($config): bool
    {
        $now = now();

        switch ($config['rotation_type']) {
            case 'time_based':
                $lastRotation = Carbon::parse($config['last_rotation'] ?? now()->subHours(1));
                return $now->diffInSeconds($lastRotation) >= $config['rotation_interval'];

            case 'request_based':
                $requestCount = $this->getRequestCount($config['user_id']);
                return $requestCount >= ($config['max_requests'] ?? 1000);

            case 'random':
                $randomInterval = rand($config['min_interval'] ?? 300, $config['max_interval'] ?? 3600);
                $lastRotation = Carbon::parse($config['last_rotation'] ?? now()->subHours(1));
                return $now->diffInSeconds($lastRotation) >= $randomInterval;

            case 'performance_based':
                return $this->shouldRotateBasedOnPerformance($config);

            default:
                return false;
        }
    }

    /**
     * Execute IP rotation for a specific configuration
     */
    private function executeRotation($config): array
    {
        try {
            $userId = $config['user_id'];
            $user = User::find($userId);

            if (!$user) {
                throw new \Exception("User not found: {$userId}");
            }

            // Get user's active proxies
            $userProxies = $this->getUserActiveProxies($user);

            if ($userProxies->isEmpty()) {
                throw new \Exception("No active proxies found for user: {$userId}");
            }

            $rotationResults = [];

            foreach ($userProxies as $proxy) {
                $rotationResult = $this->rotateProxyIP($proxy, $config);
                $rotationResults[] = $rotationResult;

                // Add delay between rotations to avoid overwhelming servers
                if (count($userProxies) > 1) {
                    sleep(rand(1, 3));
                }
            }

            // Update rotation statistics
            $this->updateRotationStatistics($config, $rotationResults);

            return [
                'success' => true,
                'user_id' => $userId,
                'rotated_proxies' => count($rotationResults),
                'rotation_results' => $rotationResults,
                'rotation_time' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("IP rotation execution error for user {$config['user_id']}: " . $e->getMessage());
            return [
                'success' => false,
                'user_id' => $config['user_id'],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rotate IP for a specific proxy
     */
    private function rotateProxyIP($proxy, $config): array
    {
        try {
            $server = $proxy['server'];
            $order = $proxy['order'];

            // Get available IPs for rotation
            $availableIPs = $this->getAvailableIPs($server, $config);

            if (empty($availableIPs)) {
                throw new \Exception("No available IPs for rotation on server {$server->id}");
            }

            // Select next IP based on rotation strategy
            $nextIP = $this->selectNextIP($availableIPs, $config);

            // Update proxy configuration with new IP
            $updateResult = $this->updateProxyIP($order, $nextIP, $config);

            if ($updateResult['success']) {
                // Log rotation event
                Log::info("IP rotated successfully", [
                    'order_id' => $order->id,
                    'server_id' => $server->id,
                    'old_ip' => $proxy['current_ip'] ?? 'unknown',
                    'new_ip' => $nextIP,
                    'rotation_type' => $config['rotation_type']
                ]);

                return [
                    'success' => true,
                    'proxy_id' => $order->id,
                    'old_ip' => $proxy['current_ip'] ?? 'unknown',
                    'new_ip' => $nextIP,
                    'server_id' => $server->id
                ];
            } else {
                throw new \Exception($updateResult['error'] ?? 'Failed to update proxy IP');
            }
        } catch (\Exception $e) {
            Log::error("Proxy IP rotation error: " . $e->getMessage());
            return [
                'success' => false,
                'proxy_id' => $proxy['order']->id ?? 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if rotation should be based on performance metrics
     */
    private function shouldRotateBasedOnPerformance($config): bool
    {
        $userId = $config['user_id'];
        $performanceMetrics = $this->getPerformanceMetrics($userId);

        // Check response time threshold
        if (isset($performanceMetrics['avg_response_time']) &&
            $performanceMetrics['avg_response_time'] > ($config['response_time_threshold'] ?? 2000)) {
            return true;
        }

        // Check error rate threshold
        if (isset($performanceMetrics['error_rate']) &&
            $performanceMetrics['error_rate'] > ($config['error_rate_threshold'] ?? 5)) {
            return true;
        }

        // Check bandwidth utilization threshold
        if (isset($performanceMetrics['bandwidth_utilization']) &&
            $performanceMetrics['bandwidth_utilization'] > ($config['bandwidth_threshold'] ?? 80)) {
            return true;
        }

        return false;
    }

    /**
     * Get active rotation configurations
     */
    private function getActiveRotationConfigs(): array
    {
        $configs = [];

        // Get all users with active rotation configurations
        $users = User::whereHas('orders', function ($query) {
            $query->where('payment_status', 'paid')
                  ->where('status', 'active');
        })->get();

        foreach ($users as $user) {
            $rotationConfig = Cache::get("rotation_config_{$user->id}");
            if ($rotationConfig && ($rotationConfig['enabled'] ?? false)) {
                $configs[] = $rotationConfig;
            }
        }

        return $configs;
    }

    /**
     * Get user's active proxies
     */
    private function getUserActiveProxies($user): \Illuminate\Support\Collection
    {
        return collect($user->orders()
            ->where('payment_status', 'paid')
            ->where('status', 'active')
            ->with(['serverPlan.server'])
            ->get()
            ->map(function ($order) {
                return [
                    'order' => $order,
                    'server' => $order->serverPlan->server,
                    'plan' => $order->serverPlan,
                    'current_ip' => $this->getCurrentProxyIP($order)
                ];
            }));
    }

    /**
     * Get available IPs for rotation
     */
    private function getAvailableIPs($server, $config): array
    {
        // In production, this would query the server's IP pool
        // For now, return mock IPs
        $baseIPs = [
            '192.168.1.',
            '10.0.0.',
            '172.16.0.',
            '203.0.113.',
            '198.51.100.'
        ];

        $availableIPs = [];
        foreach ($baseIPs as $base) {
            for ($i = 1; $i <= 10; $i++) {
                $availableIPs[] = $base . $i;
            }
        }

        // Filter out currently used IPs
        $usedIPs = $this->getUsedIPs($server);
        return array_diff($availableIPs, $usedIPs);
    }

    /**
     * Select next IP based on rotation strategy
     */
    private function selectNextIP($availableIPs, $config): string
    {
        switch ($config['selection_strategy'] ?? 'random') {
            case 'sequential':
                return $availableIPs[0];

            case 'random':
                return $availableIPs[array_rand($availableIPs)];

            case 'geographic':
                return $this->selectGeographicIP($availableIPs, $config);

            case 'performance':
                return $this->selectPerformanceBasedIP($availableIPs, $config);

            default:
                return $availableIPs[array_rand($availableIPs)];
        }
    }

    /**
     * Update proxy IP configuration
     */
    private function updateProxyIP($order, $newIP, $config): array
    {
        try {
            // Here you would integrate with your proxy management system
            // For now, simulate the update

            // Update proxy configuration in database
            $proxyConfig = $order->proxy_config ?? [];
            $proxyConfig['current_ip'] = $newIP;
            $proxyConfig['last_rotation'] = now()->toISOString();
            $proxyConfig['rotation_count'] = ($proxyConfig['rotation_count'] ?? 0) + 1;

            $order->update(['proxy_config' => $proxyConfig]);

            // Cache the new IP for quick access
            Cache::put("proxy_ip_{$order->id}", $newIP, 3600);

            return [
                'success' => true,
                'new_ip' => $newIP,
                'update_time' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update rotation statistics
     */
    private function updateRotationStatistics($config, $results): void
    {
        $stats = Cache::get("rotation_stats_{$config['user_id']}", [
            'total_rotations' => 0,
            'successful_rotations' => 0,
            'failed_rotations' => 0,
            'last_rotation' => null
        ]);

        $successfulRotations = collect($results)->where('success', true)->count();
        $failedRotations = collect($results)->where('success', false)->count();

        $stats['total_rotations'] += count($results);
        $stats['successful_rotations'] += $successfulRotations;
        $stats['failed_rotations'] += $failedRotations;
        $stats['last_rotation'] = now()->toISOString();

        Cache::put("rotation_stats_{$config['user_id']}", $stats, 86400);

        // Update configuration with last rotation time
        $config['last_rotation'] = now()->toISOString();
        Cache::put("rotation_config_{$config['user_id']}", $config, 3600);
    }

    /**
     * Update next rotation time
     */
    private function updateNextRotationTime($config): void
    {
        $nextRotation = now()->addSeconds($config['rotation_interval']);
        Cache::put("next_rotation_{$config['user_id']}", $nextRotation->toISOString(), 3600);
    }

    // Helper methods with mock implementations
    private function getRequestCount($userId): int { return rand(500, 1500); }
    private function getPerformanceMetrics($userId): array {
        return [
            'avg_response_time' => rand(100, 3000),
            'error_rate' => rand(1, 10),
            'bandwidth_utilization' => rand(30, 95)
        ];
    }
    private function getCurrentProxyIP($order): string {
        return Cache::get("proxy_ip_{$order->id}", '192.168.1.' . rand(1, 255));
    }
    private function getUsedIPs($server): array {
        return ['192.168.1.1', '192.168.1.2', '10.0.0.1'];
    }
    private function selectGeographicIP($availableIPs, $config): string {
        return $availableIPs[array_rand($availableIPs)];
    }
    private function selectPerformanceBasedIP($availableIPs, $config): string {
        return $availableIPs[array_rand($availableIPs)];
    }
}
