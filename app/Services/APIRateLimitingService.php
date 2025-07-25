<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class APIRateLimitingService
{
    protected $redis;
    protected $config;
    protected $rateLimits;

    public function __construct()
    {
        $this->redis = Redis::connection();
        $this->config = Config::get('api.rate_limiting', []);
        $this->initializeRateLimits();
    }

    /**
     * Initialize rate limit configurations
     */
    protected function initializeRateLimits(): void
    {
        $this->rateLimits = [
            'authentication' => [
                'limit' => 5,
                'window' => 60, // 1 minute
                'key_prefix' => 'auth_limit',
                'throttle_duration' => 300, // 5 minutes for violations
                'description' => 'Authentication endpoints (login, register, etc.)'
            ],
            'api_general' => [
                'limit' => 100,
                'window' => 60, // 1 minute
                'key_prefix' => 'api_limit',
                'throttle_duration' => 60,
                'description' => 'General API endpoints'
            ],
            'mobile_api' => [
                'limit' => 200,
                'window' => 60, // 1 minute
                'key_prefix' => 'mobile_limit',
                'throttle_duration' => 30,
                'description' => 'Mobile app specific endpoints'
            ],
            'admin_api' => [
                'limit' => 500,
                'window' => 60, // 1 minute
                'key_prefix' => 'admin_limit',
                'throttle_duration' => 30,
                'description' => 'Admin panel API endpoints'
            ],
            'public_api' => [
                'limit' => 50,
                'window' => 60, // 1 minute
                'key_prefix' => 'public_limit',
                'throttle_duration' => 120,
                'description' => 'Public endpoints (no authentication required)'
            ],
            'webhook_api' => [
                'limit' => 30,
                'window' => 60, // 1 minute
                'key_prefix' => 'webhook_limit',
                'throttle_duration' => 300,
                'description' => 'Webhook endpoints'
            ]
        ];
    }

    /**
     * Check if request should be rate limited
     */
    public function shouldLimit(Request $request, string $limitType = 'api_general'): array
    {
        if (!isset($this->rateLimits[$limitType])) {
            throw new \InvalidArgumentException("Unknown rate limit type: {$limitType}");
        }

        $config = $this->rateLimits[$limitType];
        $identifier = $this->getIdentifier($request, $limitType);
        $key = "{$config['key_prefix']}:{$identifier}";

        // Check if IP is currently throttled
        $throttleKey = "throttle:{$config['key_prefix']}:{$identifier}";
        if ($this->redis->exists($throttleKey)) {
            return [
                'limited' => true,
                'reason' => 'throttled',
                'retry_after' => $this->redis->ttl($throttleKey),
                'limit' => $config['limit'],
                'remaining' => 0,
                'reset_time' => time() + $this->redis->ttl($throttleKey)
            ];
        }

        // Get current request count
        $currentCount = (int) $this->redis->get($key);
        $remaining = max(0, $config['limit'] - $currentCount);

        // Calculate reset time
        $resetTime = $this->getResetTime($key, $config['window']);

        if ($currentCount >= $config['limit']) {
            // Apply throttling for repeated violations
            $this->applyThrottling($identifier, $config);

            return [
                'limited' => true,
                'reason' => 'rate_limit_exceeded',
                'retry_after' => $resetTime - time(),
                'limit' => $config['limit'],
                'remaining' => 0,
                'reset_time' => $resetTime
            ];
        }

        return [
            'limited' => false,
            'limit' => $config['limit'],
            'remaining' => $remaining - 1, // Account for current request
            'reset_time' => $resetTime
        ];
    }

    /**
     * Record a request for rate limiting
     */
    public function recordRequest(Request $request, string $limitType = 'api_general'): void
    {
        if (!isset($this->rateLimits[$limitType])) {
            return;
        }

        $config = $this->rateLimits[$limitType];
        $identifier = $this->getIdentifier($request, $limitType);
        $key = "{$config['key_prefix']}:{$identifier}";

        // Increment request count
        $pipeline = $this->redis->pipeline();
        $pipeline->incr($key);
        $pipeline->expire($key, $config['window']);
        $pipeline->execute();

        // Log request for analytics
        $this->logRequest($request, $limitType, $identifier);
    }

    /**
     * Get unique identifier for rate limiting
     */
    protected function getIdentifier(Request $request, string $limitType): string
    {
        $parts = [];

        // Always include IP
        $parts[] = $request->ip();

        // Add user ID for authenticated requests
        if ($request->user()) {
            $parts[] = 'user:' . $request->user()->id;
        }

        // Add API key if present
        if ($apiKey = $request->header('X-API-Key')) {
            $parts[] = 'api_key:' . substr(hash('sha256', $apiKey), 0, 8);
        }

        // Add device ID for mobile apps
        if ($deviceId = $request->header('X-Device-ID')) {
            $parts[] = 'device:' . substr(hash('sha256', $deviceId), 0, 8);
        }

        // Special handling for different limit types
        switch ($limitType) {
            case 'authentication':
                // For auth endpoints, use IP + email if provided
                if ($email = $request->input('email')) {
                    $parts[] = 'email:' . substr(hash('sha256', $email), 0, 8);
                }
                break;

            case 'mobile_api':
                // For mobile, prioritize device ID over IP
                if (!$request->header('X-Device-ID')) {
                    $parts[] = 'mobile_no_device';
                }
                break;
        }

        return implode(':', $parts);
    }

    /**
     * Apply throttling for rate limit violations
     */
    protected function applyThrottling(string $identifier, array $config): void
    {
        $throttleKey = "throttle:{$config['key_prefix']}:{$identifier}";
        $violationKey = "violations:{$config['key_prefix']}:{$identifier}";

        // Track violations for progressive throttling
        $violations = (int) $this->redis->incr($violationKey);
        $this->redis->expire($violationKey, 3600); // Reset violations after 1 hour

        // Progressive throttling - longer delays for repeat offenders
        $throttleDuration = $config['throttle_duration'] * min(5, $violations);

        $this->redis->setex($throttleKey, $throttleDuration, $violations);

        // Log throttling event
        Log::warning('Rate limit throttling applied', [
            'identifier' => $identifier,
            'limit_type' => $config['key_prefix'],
            'violations' => $violations,
            'throttle_duration' => $throttleDuration
        ]);
    }

    /**
     * Get reset time for rate limit window
     */
    protected function getResetTime(string $key, int $window): int
    {
        $ttl = $this->redis->ttl($key);
        if ($ttl > 0) {
            return time() + $ttl;
        }
        return time() + $window;
    }

    /**
     * Log request for analytics
     */
    protected function logRequest(Request $request, string $limitType, string $identifier): void
    {
        $logData = [
            'timestamp' => time(),
            'limit_type' => $limitType,
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'api_key_hash' => $request->header('X-API-Key') ? substr(hash('sha256', $request->header('X-API-Key')), 0, 8) : null
        ];

        // Store in Redis list for analytics (keep last 10000 requests)
        $analyticsKey = "rate_limit_analytics";
        $this->redis->lpush($analyticsKey, json_encode($logData));
        $this->redis->ltrim($analyticsKey, 0, 9999);
    }

    /**
     * Get rate limit status for an identifier
     */
    public function getStatus(Request $request, string $limitType = 'api_general'): array
    {
        if (!isset($this->rateLimits[$limitType])) {
            throw new \InvalidArgumentException("Unknown rate limit type: {$limitType}");
        }

        $config = $this->rateLimits[$limitType];
        $identifier = $this->getIdentifier($request, $limitType);
        $key = "{$config['key_prefix']}:{$identifier}";

        $currentCount = (int) $this->redis->get($key);
        $remaining = max(0, $config['limit'] - $currentCount);
        $resetTime = $this->getResetTime($key, $config['window']);

        // Check throttling status
        $throttleKey = "throttle:{$config['key_prefix']}:{$identifier}";
        $isThrottled = $this->redis->exists($throttleKey);
        $throttleRetryAfter = $isThrottled ? $this->redis->ttl($throttleKey) : 0;

        return [
            'limit' => $config['limit'],
            'remaining' => $remaining,
            'reset_time' => $resetTime,
            'current_count' => $currentCount,
            'is_throttled' => $isThrottled,
            'throttle_retry_after' => $throttleRetryAfter,
            'window_seconds' => $config['window']
        ];
    }

    /**
     * Clear rate limits for an identifier (admin function)
     */
    public function clearLimits(string $identifier, string $limitType = null): void
    {
        $typesToClear = $limitType ? [$limitType] : array_keys($this->rateLimits);

        foreach ($typesToClear as $type) {
            if (!isset($this->rateLimits[$type])) {
                continue;
            }

            $config = $this->rateLimits[$type];
            $keys = [
                "{$config['key_prefix']}:{$identifier}",
                "throttle:{$config['key_prefix']}:{$identifier}",
                "violations:{$config['key_prefix']}:{$identifier}"
            ];

            foreach ($keys as $key) {
                $this->redis->del($key);
            }
        }

        Log::info('Rate limits cleared for identifier', [
            'identifier' => $identifier,
            'types' => $typesToClear
        ]);
    }

    /**
     * Get rate limiting analytics
     */
    public function getAnalytics(int $hours = 24): array
    {
        $analyticsKey = "rate_limit_analytics";
        $logs = $this->redis->lrange($analyticsKey, 0, -1);

        $since = time() - ($hours * 3600);
        $analytics = [
            'total_requests' => 0,
            'by_limit_type' => [],
            'by_hour' => [],
            'top_ips' => [],
            'throttled_requests' => 0,
            'violation_patterns' => []
        ];

        $ipCounts = [];
        $hourCounts = [];

        foreach ($logs as $logJson) {
            $log = json_decode($logJson, true);
            if (!$log || $log['timestamp'] < $since) {
                continue;
            }

            $analytics['total_requests']++;

            // Count by limit type
            $limitType = $log['limit_type'];
            $analytics['by_limit_type'][$limitType] = ($analytics['by_limit_type'][$limitType] ?? 0) + 1;

            // Count by hour
            $hour = date('H', $log['timestamp']);
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;

            // Count by IP
            $ip = $log['ip'];
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
        }

        // Process hourly data
        for ($h = 0; $h < 24; $h++) {
            $hour = str_pad($h, 2, '0', STR_PAD_LEFT);
            $analytics['by_hour'][$hour] = $hourCounts[$h] ?? 0;
        }

        // Get top IPs
        arsort($ipCounts);
        $analytics['top_ips'] = array_slice($ipCounts, 0, 10, true);

        // Get current throttling information
        $analytics['currently_throttled'] = $this->getCurrentlyThrottled();

        return $analytics;
    }

    /**
     * Get currently throttled identifiers
     */
    protected function getCurrentlyThrottled(): array
    {
        $throttled = [];
        $patterns = [];

        foreach ($this->rateLimits as $type => $config) {
            $patterns[] = "throttle:{$config['key_prefix']}:*";
        }

        foreach ($patterns as $pattern) {
            $keys = $this->redis->keys($pattern);
            foreach ($keys as $key) {
                $ttl = $this->redis->ttl($key);
                $violations = $this->redis->get($key);

                $throttled[] = [
                    'key' => $key,
                    'ttl' => $ttl,
                    'violations' => $violations,
                    'expires_at' => time() + $ttl
                ];
            }
        }

        return $throttled;
    }

    /**
     * Configure custom rate limits
     */
    public function setCustomLimit(string $name, array $config): void
    {
        $requiredFields = ['limit', 'window', 'key_prefix'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $config['throttle_duration'] = $config['throttle_duration'] ?? 60;
        $config['description'] = $config['description'] ?? "Custom rate limit: {$name}";

        $this->rateLimits[$name] = $config;
    }

    /**
     * Get rate limit configuration
     */
    public function getConfiguration(): array
    {
        return [
            'rate_limits' => $this->rateLimits,
            'redis_connection' => $this->redis->connection()->getName(),
            'analytics_retention' => '10,000 recent requests',
            'violation_reset_time' => '1 hour',
            'progressive_throttling' => 'Enabled (up to 5x base duration)'
        ];
    }

    /**
     * Validate rate limit headers
     */
    public function validateHeaders(Request $request): array
    {
        $issues = [];

        // Check for required headers in mobile requests
        if (str_contains($request->path(), 'mobile')) {
            if (!$request->header('X-Device-ID')) {
                $issues[] = 'Missing X-Device-ID header for mobile API';
            }
        }

        // Check API key format
        if ($apiKey = $request->header('X-API-Key')) {
            if (strlen($apiKey) < 32) {
                $issues[] = 'API key appears to be too short';
            }
        }

        return $issues;
    }

    /**
     * Generate rate limit response headers
     */
    public function generateHeaders(array $status): array
    {
        return [
            'X-RateLimit-Limit' => $status['limit'],
            'X-RateLimit-Remaining' => $status['remaining'],
            'X-RateLimit-Reset' => $status['reset_time'],
            'X-RateLimit-Window' => $status['window_seconds']
        ];
    }

    /**
     * Clean up old analytics data
     */
    public function cleanupAnalytics(): int
    {
        $analyticsKey = "rate_limit_analytics";
        $currentSize = $this->redis->llen($analyticsKey);

        // Keep only last 5000 entries
        $this->redis->ltrim($analyticsKey, 0, 4999);

        $removedCount = max(0, $currentSize - 5000);

        if ($removedCount > 0) {
            Log::info('Rate limit analytics cleanup completed', [
                'removed_entries' => $removedCount,
                'remaining_entries' => 5000
            ]);
        }

        return $removedCount;
    }
}
