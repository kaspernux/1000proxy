<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $rateLimitName = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $rateLimitName);

        // Check if IP is temporarily blocked
        if ($this->isTemporarilyBlocked($request)) {
            Log::warning('Blocked IP attempted access', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent()
            ]);
            throw new TooManyRequestsHttpException(3600, 'Too many requests. IP temporarily blocked.');
        }

        // Apply rate limiting based on the type
        $this->applyRateLimit($request, $key, $rateLimitName);

        $response = $next($request);

        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $key, $rateLimitName);

        return $response;
    }

    /**
     * Apply rate limiting based on the rate limit name
     */
    private function applyRateLimit(Request $request, string $key, string $rateLimitName): void
    {
        $limits = $this->getRateLimits($rateLimitName);

        foreach ($limits as $limit) {
            $limitKey = $key . ':' . $limit['window'];

            if (RateLimiter::tooManyAttempts($limitKey, $limit['maxAttempts'])) {
                // Log excessive requests
                Log::warning('Rate limit exceeded', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'limit_type' => $rateLimitName,
                    'limit' => $limit['maxAttempts'] . '/' . $limit['window'] . 's'
                ]);

                // Check for potential abuse
                if ($this->isPotentialAbuse($request, $limitKey)) {
                    $this->temporarilyBlockIP($request);
                }

                $retryAfter = RateLimiter::availableIn($limitKey);
                throw new TooManyRequestsHttpException($retryAfter, 'Rate limit exceeded for ' . $rateLimitName);
            }

            RateLimiter::hit($limitKey, $limit['window']);
        }
    }

    /**
     * Get rate limits for different types of requests
     */
    private function getRateLimits(string $rateLimitName): array
    {
        $limits = [
            'default' => [
                ['maxAttempts' => 60, 'window' => 60],     // 60 requests per minute
                ['maxAttempts' => 1000, 'window' => 3600]  // 1000 requests per hour
            ],
            'auth' => [
                ['maxAttempts' => 5, 'window' => 300],     // 5 login attempts per 5 minutes
                ['maxAttempts' => 20, 'window' => 3600]    // 20 login attempts per hour
            ],
            'api' => [
                ['maxAttempts' => 100, 'window' => 60],    // 100 API calls per minute
                ['maxAttempts' => 2000, 'window' => 3600]  // 2000 API calls per hour
            ],
            'admin' => [
                ['maxAttempts' => 30, 'window' => 60],     // 30 admin requests per minute
                ['maxAttempts' => 500, 'window' => 3600]   // 500 admin requests per hour
            ],
            'payment' => [
                ['maxAttempts' => 3, 'window' => 300],     // 3 payment attempts per 5 minutes
                ['maxAttempts' => 10, 'window' => 3600]    // 10 payment attempts per hour
            ],
            'register' => [
                ['maxAttempts' => 3, 'window' => 300],     // 3 registration attempts per 5 minutes
                ['maxAttempts' => 5, 'window' => 3600]     // 5 registration attempts per hour
            ],
            'password-reset' => [
                ['maxAttempts' => 3, 'window' => 300],     // 3 password reset attempts per 5 minutes
                ['maxAttempts' => 10, 'window' => 3600]    // 10 password reset attempts per hour
            ]
        ];

        return $limits[$rateLimitName] ?? $limits['default'];
    }

    /**
     * Resolve the request signature for rate limiting
     */
    private function resolveRequestSignature(Request $request, string $rateLimitName): string
    {
        $baseKey = 'rate_limit:' . $rateLimitName;

        // For authenticated users, use user ID
        if ($request->user()) {
            return $baseKey . ':user:' . $request->user()->id;
        }

        // For guest users, use IP address with additional context
        $ipKey = $baseKey . ':ip:' . $request->ip();

        // Add path context for more granular limiting
        if (in_array($rateLimitName, ['auth', 'payment', 'register'])) {
            $ipKey .= ':' . md5($request->path());
        }

        return $ipKey;
    }

    /**
     * Add rate limit headers to the response
     */
    private function addRateLimitHeaders(Response $response, string $key, string $rateLimitName): void
    {
        $limits = $this->getRateLimits($rateLimitName);
        $primaryLimit = $limits[0]; // Use the most restrictive limit for headers

        $limitKey = $key . ':' . $primaryLimit['window'];
        $attempts = RateLimiter::attempts($limitKey);
        $remaining = max(0, $primaryLimit['maxAttempts'] - $attempts);
        $resetTime = RateLimiter::availableIn($limitKey);

        $response->headers->set('X-RateLimit-Limit', $primaryLimit['maxAttempts']);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($resetTime)->timestamp);
        $response->headers->set('X-RateLimit-Window', $primaryLimit['window']);
    }

    /**
     * Check if this appears to be potential abuse
     */
    private function isPotentialAbuse(Request $request, string $limitKey): bool
    {
        // Count how many times this IP has hit rate limits in the last hour
        $abuseKey = 'abuse_check:' . $request->ip();
        $abuseCount = Cache::get($abuseKey, 0);

        // If they've hit rate limits more than 5 times in an hour, consider it abuse
        if ($abuseCount >= 5) {
            return true;
        }

        // Increment abuse counter
        Cache::put($abuseKey, $abuseCount + 1, now()->addHour());

        return false;
    }

    /**
     * Temporarily block an IP address
     */
    private function temporarilyBlockIP(Request $request): void
    {
        $blockKey = 'blocked_ip:' . $request->ip();
        Cache::put($blockKey, true, now()->addHour()); // Block for 1 hour

        Log::critical('IP address temporarily blocked for abuse', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path()
        ]);
    }

    /**
     * Check if an IP is temporarily blocked
     */
    private function isTemporarilyBlocked(Request $request): bool
    {
        $blockKey = 'blocked_ip:' . $request->ip();
        return Cache::has($blockKey);
    }
}
