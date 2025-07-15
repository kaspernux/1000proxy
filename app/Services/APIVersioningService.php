<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class APIVersioningService
{
    protected $versions;
    protected $currentVersion;
    protected $deprecationPolicy;

    public function __construct()
    {
        $this->initializeVersions();
        $this->currentVersion = 'v2';
        $this->deprecationPolicy = [
            'support_duration_months' => 12,
            'warning_period_months' => 6,
            'migration_assistance_period_months' => 3
        ];
    }

    /**
     * Initialize version configurations
     */
    protected function initializeVersions(): void
    {
        $this->versions = [
            'v1' => [
                'version' => '1.0.0',
                'status' => 'deprecated',
                'release_date' => '2024-06-01',
                'deprecation_date' => '2024-12-01',
                'end_of_life' => '2025-12-01',
                'description' => 'Initial API version with basic functionality',
                'features' => [
                    'basic_authentication' => 'Basic HTTP authentication',
                    'crud_operations' => 'Standard CRUD operations for resources',
                    'simple_error_handling' => 'Basic error responses',
                    'pagination' => 'Simple page-based pagination'
                ],
                'limitations' => [
                    'No JWT authentication',
                    'Limited error context',
                    'No real-time capabilities',
                    'Basic rate limiting',
                    'No mobile optimizations'
                ],
                'endpoints' => [
                    'base_path' => '/api/v1',
                    'authentication' => '/api/auth',
                    'users' => '/api/users',
                    'servers' => '/api/servers',
                    'orders' => '/api/orders'
                ]
            ],
            'v2' => [
                'version' => '2.0.0',
                'status' => 'current',
                'release_date' => '2025-01-01',
                'deprecation_date' => null,
                'end_of_life' => null,
                'description' => 'Enhanced API with improved authentication, error handling, and real-time features',
                'features' => [
                    'jwt_authentication' => 'JWT token-based authentication',
                    'enhanced_error_handling' => 'Comprehensive error responses with context',
                    'real_time_support' => 'WebSocket integration for real-time updates',
                    'mobile_optimization' => 'Mobile-specific endpoints and optimizations',
                    'advanced_filtering' => 'Complex filtering and search capabilities',
                    'comprehensive_rate_limiting' => 'Advanced rate limiting with throttling',
                    'api_key_authentication' => 'API key support for server-to-server communication',
                    'batch_operations' => 'Batch processing for bulk operations',
                    'webhook_support' => 'Webhook notifications for events'
                ],
                'improvements' => [
                    'Performance optimizations',
                    'Better documentation with OpenAPI 3.0',
                    'Improved pagination with meta information',
                    'Enhanced security features',
                    'Mobile app ready endpoints',
                    'Real-time notifications',
                    'Advanced analytics and monitoring'
                ],
                'endpoints' => [
                    'base_path' => '/api/v2',
                    'authentication' => '/api/v2/auth',
                    'users' => '/api/v2/users',
                    'customers' => '/api/v2/customers',
                    'servers' => '/api/v2/servers',
                    'orders' => '/api/v2/orders',
                    'payments' => '/api/v2/payments',
                    'mobile' => '/api/v2/mobile',
                    'webhooks' => '/api/v2/webhooks',
                    'admin' => '/api/v2/admin'
                ]
            ]
        ];
    }

    /**
     * Determine API version from request
     */
    public function determineVersion(Request $request): string
    {
        // Check URL path first
        $path = $request->path();
        if (preg_match('/^api\/(v\d+)\//', $path, $matches)) {
            return $matches[1];
        }

        // Check Accept header for version preference
        $acceptHeader = $request->header('Accept');
        if (preg_match('/application\/vnd\.1000proxy\.(v\d+)\+json/', $acceptHeader, $matches)) {
            return $matches[1];
        }

        // Check custom version header
        if ($version = $request->header('X-API-Version')) {
            return $version;
        }

        // Default to current version
        return $this->currentVersion;
    }

    /**
     * Validate if version is supported
     */
    public function isVersionSupported(string $version): bool
    {
        return isset($this->versions[$version]) &&
               $this->versions[$version]['status'] !== 'discontinued';
    }

    /**
     * Get version information
     */
    public function getVersionInfo(string $version): ?array
    {
        if (!isset($this->versions[$version])) {
            return null;
        }

        $info = $this->versions[$version];

        // Add computed fields
        $info['is_deprecated'] = $info['status'] === 'deprecated';
        $info['is_current'] = $info['status'] === 'current';
        $info['days_until_eol'] = $info['end_of_life'] ?
            Carbon::parse($info['end_of_life'])->diffInDays(now(), false) : null;

        return $info;
    }

    /**
     * Get migration guide for version upgrade
     */
    public function getMigrationGuide(string $fromVersion, string $toVersion): array
    {
        if (!$this->isVersionSupported($fromVersion) || !$this->isVersionSupported($toVersion)) {
            throw new \InvalidArgumentException('Invalid version specified');
        }

        $guides = [
            'v1_to_v2' => [
                'overview' => 'Migration from API v1 to v2 involves updating authentication, error handling, and endpoint usage',
                'breaking_changes' => [
                    'authentication' => [
                        'change' => 'Basic authentication replaced with JWT tokens',
                        'action' => 'Update client to use /api/v2/auth/login endpoint and include Bearer tokens in requests',
                        'example' => [
                            'old' => 'Authorization: Basic base64(username:password)',
                            'new' => 'Authorization: Bearer jwt_token_here'
                        ]
                    ],
                    'error_responses' => [
                        'change' => 'Error response format enhanced with more context',
                        'action' => 'Update error handling to parse new error response format',
                        'example' => [
                            'old' => '{"error": "Validation failed"}',
                            'new' => '{"error": {"code": "VALIDATION_ERROR", "message": "Validation failed", "details": {...}}}'
                        ]
                    ],
                    'pagination' => [
                        'change' => 'Pagination enhanced with meta information',
                        'action' => 'Update pagination logic to use new meta and links structure',
                        'example' => [
                            'old' => '{"data": [...], "total": 100, "page": 1}',
                            'new' => '{"data": [...], "meta": {...}, "links": {...}}'
                        ]
                    ]
                ],
                'new_features' => [
                    'real_time' => 'WebSocket support for real-time updates',
                    'mobile_endpoints' => 'Mobile-optimized endpoints under /api/v2/mobile',
                    'api_keys' => 'Server-to-server authentication with API keys',
                    'advanced_filtering' => 'Enhanced filtering with complex query support',
                    'batch_operations' => 'Bulk operations for efficiency'
                ],
                'endpoint_changes' => [
                    '/api/auth' => '/api/v2/auth (enhanced with JWT)',
                    '/api/users' => '/api/v2/users (additional fields and operations)',
                    '/api/servers' => '/api/v2/servers (real-time status, enhanced filtering)',
                    '/api/orders' => '/api/v2/orders (batch operations, webhooks)',
                    'new: /api/v2/customers' => 'Dedicated customer management endpoints',
                    'new: /api/v2/payments' => 'Enhanced payment processing endpoints',
                    'new: /api/v2/mobile/*' => 'Mobile app specific endpoints'
                ],
                'timeline' => [
                    'immediate' => 'Start using v2 endpoints for new integrations',
                    '3_months' => 'Complete migration of critical systems',
                    '6_months' => 'All systems should be using v2',
                    '12_months' => 'v1 will be discontinued'
                ],
                'testing_strategy' => [
                    'parallel_testing' => 'Run v1 and v2 in parallel to validate responses',
                    'gradual_migration' => 'Migrate non-critical endpoints first',
                    'fallback_plan' => 'Keep v1 integration as backup during transition'
                ]
            ]
        ];

        $key = "{$fromVersion}_to_{$toVersion}";
        return $guides[$key] ?? [
            'message' => 'No specific migration guide available for this version combination',
            'recommendation' => 'Please contact API support for assistance'
        ];
    }

    /**
     * Check if version requires deprecation warning
     */
    public function shouldShowDeprecationWarning(string $version): bool
    {
        $versionInfo = $this->getVersionInfo($version);

        if (!$versionInfo || $versionInfo['status'] !== 'deprecated') {
            return false;
        }

        // Show warning if end of life is within 6 months
        if ($versionInfo['end_of_life']) {
            $eolDate = Carbon::parse($versionInfo['end_of_life']);
            return $eolDate->diffInMonths(now()) <= 6;
        }

        return true;
    }

    /**
     * Generate deprecation warning headers
     */
    public function getDeprecationHeaders(string $version): array
    {
        $headers = [];

        if ($this->shouldShowDeprecationWarning($version)) {
            $versionInfo = $this->getVersionInfo($version);

            $headers['X-API-Deprecated'] = 'true';
            $headers['X-API-Deprecation-Date'] = $versionInfo['deprecation_date'] ?? 'unknown';

            if ($versionInfo['end_of_life']) {
                $headers['X-API-End-Of-Life'] = $versionInfo['end_of_life'];
            }

            $headers['X-API-Migration-Guide'] = url("/api/docs/migration/{$version}-to-{$this->currentVersion}");
            $headers['Warning'] = '299 - "This API version is deprecated. Please migrate to ' . $this->currentVersion . '"';
        }

        return $headers;
    }

    /**
     * Get supported versions list
     */
    public function getSupportedVersions(): array
    {
        $supported = [];

        foreach ($this->versions as $version => $info) {
            if ($info['status'] !== 'discontinued') {
                $supported[$version] = [
                    'version' => $info['version'],
                    'status' => $info['status'],
                    'is_current' => $version === $this->currentVersion,
                    'base_url' => url($info['endpoints']['base_path'])
                ];
            }
        }

        return $supported;
    }

    /**
     * Log version usage for analytics
     */
    public function logVersionUsage(Request $request, string $version): void
    {
        $logData = [
            'timestamp' => time(),
            'version' => $version,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'api_key_hash' => $request->header('X-API-Key') ?
                substr(hash('sha256', $request->header('X-API-Key')), 0, 8) : null
        ];

        // Store in cache for analytics
        $key = 'api_version_usage:' . date('Y-m-d');
        $usage = Cache::get($key, []);
        $usage[] = $logData;

        // Keep only last 1000 entries per day
        if (count($usage) > 1000) {
            $usage = array_slice($usage, -1000);
        }

        Cache::put($key, $usage, now()->addDays(30));
    }

    /**
     * Get version usage analytics
     */
    public function getVersionAnalytics(int $days = 7): array
    {
        $analytics = [
            'summary' => [
                'total_requests' => 0,
                'by_version' => [],
                'deprecated_usage' => 0
            ],
            'daily_breakdown' => [],
            'endpoint_usage' => [],
            'migration_progress' => []
        ];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $key = 'api_version_usage:' . $date;
            $usage = Cache::get($key, []);

            $dailyStats = [
                'date' => $date,
                'total' => count($usage),
                'by_version' => []
            ];

            foreach ($usage as $request) {
                $version = $request['version'];

                // Count by version
                $analytics['summary']['by_version'][$version] =
                    ($analytics['summary']['by_version'][$version] ?? 0) + 1;

                $dailyStats['by_version'][$version] =
                    ($dailyStats['by_version'][$version] ?? 0) + 1;

                // Count deprecated usage
                if ($this->getVersionInfo($version)['is_deprecated'] ?? false) {
                    $analytics['summary']['deprecated_usage']++;
                }

                // Count endpoint usage
                $endpoint = $request['endpoint'];
                $analytics['endpoint_usage'][$endpoint] =
                    ($analytics['endpoint_usage'][$endpoint] ?? 0) + 1;
            }

            $analytics['daily_breakdown'][] = $dailyStats;
            $analytics['summary']['total_requests'] += $dailyStats['total'];
        }

        // Calculate migration progress
        $totalRequests = $analytics['summary']['total_requests'];
        if ($totalRequests > 0) {
            $currentVersionUsage = $analytics['summary']['by_version'][$this->currentVersion] ?? 0;
            $analytics['migration_progress'] = [
                'current_version_adoption' => round(($currentVersionUsage / $totalRequests) * 100, 2),
                'deprecated_version_usage' => round(($analytics['summary']['deprecated_usage'] / $totalRequests) * 100, 2)
            ];
        }

        return $analytics;
    }

    /**
     * Get version compatibility matrix
     */
    public function getCompatibilityMatrix(): array
    {
        return [
            'client_compatibility' => [
                'mobile_apps' => [
                    'ios' => ['minimum_version' => 'v2', 'recommended' => 'v2'],
                    'android' => ['minimum_version' => 'v2', 'recommended' => 'v2'],
                    'react_native' => ['minimum_version' => 'v2', 'recommended' => 'v2']
                ],
                'web_applications' => [
                    'spa_apps' => ['minimum_version' => 'v1', 'recommended' => 'v2'],
                    'traditional_web' => ['minimum_version' => 'v1', 'recommended' => 'v2']
                ],
                'server_integrations' => [
                    'webhooks' => ['minimum_version' => 'v2', 'recommended' => 'v2'],
                    'batch_processing' => ['minimum_version' => 'v2', 'recommended' => 'v2'],
                    'real_time' => ['minimum_version' => 'v2', 'recommended' => 'v2']
                ]
            ],
            'feature_compatibility' => [
                'authentication' => [
                    'basic_auth' => ['v1', 'v2'],
                    'jwt_tokens' => ['v2'],
                    'api_keys' => ['v2']
                ],
                'real_time' => [
                    'websockets' => ['v2'],
                    'server_sent_events' => ['v2']
                ],
                'mobile_features' => [
                    'push_notifications' => ['v2'],
                    'offline_support' => ['v2'],
                    'device_management' => ['v2']
                ]
            ]
        ];
    }

    /**
     * Validate version in request
     */
    public function validateVersionRequest(Request $request): array
    {
        $version = $this->determineVersion($request);
        $issues = [];

        // Check if version exists
        if (!isset($this->versions[$version])) {
            $issues[] = "Unknown API version: {$version}";
            return ['valid' => false, 'issues' => $issues, 'version' => $version];
        }

        $versionInfo = $this->getVersionInfo($version);

        // Check if version is discontinued
        if ($versionInfo['status'] === 'discontinued') {
            $issues[] = "API version {$version} has been discontinued";
        }

        // Check deprecation warnings
        if ($this->shouldShowDeprecationWarning($version)) {
            $eolDate = $versionInfo['end_of_life'];
            $issues[] = "API version {$version} is deprecated and will be discontinued on {$eolDate}";
        }

        return [
            'valid' => empty($issues) || $versionInfo['status'] !== 'discontinued',
            'issues' => $issues,
            'version' => $version,
            'version_info' => $versionInfo
        ];
    }

    /**
     * Get current version information
     */
    public function getCurrentVersion(): array
    {
        return $this->getVersionInfo($this->currentVersion);
    }

    /**
     * Set deprecation for a version
     */
    public function deprecateVersion(string $version, string $endOfLife): void
    {
        if (!isset($this->versions[$version])) {
            throw new \InvalidArgumentException("Version {$version} does not exist");
        }

        $this->versions[$version]['status'] = 'deprecated';
        $this->versions[$version]['deprecation_date'] = now()->format('Y-m-d');
        $this->versions[$version]['end_of_life'] = $endOfLife;

        Log::info("API version {$version} has been deprecated", [
            'version' => $version,
            'end_of_life' => $endOfLife
        ]);

        // Clear relevant caches
        Cache::forget('api_versions_supported');
    }
}
