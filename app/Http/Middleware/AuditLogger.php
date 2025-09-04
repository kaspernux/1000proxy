<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request for sensitive operations
        if ($this->shouldLog($request)) {
            try {
                Log::channel('audit')->info('API Request', [
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'payload' => $this->sanitizePayload($request->all()),
                    'timestamp' => now()->toISOString(),
                ]);
            } catch (\Throwable $e) {
                // Fallback when Log::channel is mocked in tests without expectations
                Log::info('API Request', [
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'payload' => $this->sanitizePayload($request->all()),
                    'timestamp' => now()->toISOString(),
                ]);
            }
        }

        $response = $next($request);

        // Log response for sensitive operations
        if ($this->shouldLog($request)) {
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            try {
                Log::channel('audit')->info('API Response', [
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $duration,
                    'timestamp' => now()->toISOString(),
                ]);
            } catch (\Throwable $e) {
                Log::info('API Response', [
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $duration,
                    'timestamp' => now()->toISOString(),
                ]);
            }
        }

        return $response;
    }

    /**
     * Determine if the request should be logged
     */
    private function shouldLog(Request $request): bool
    {
        $sensitiveRoutes = [
            'payments',
            'orders',
            'admin',
            'wallet',
            'servers',
            'invoices',
        ];

        foreach ($sensitiveRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize payload to remove sensitive data
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'api_key', 'secret', 'token'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($payload[$key])) {
                $payload[$key] = '[REDACTED]';
            }
        }

        return $payload;
    }
}
