<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log security events for monitoring
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Suspicious request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'headers' => $request->headers->all()
            ]);
        }

        // Remove sensitive headers that might leak information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        return $response;
    }

    // Keep all other methods except buildContentSecurityPolicy()
    // Remove the entire buildContentSecurityPolicy() method

    private function getFrameOptions(Request $request): string
    {
        if ($request->is('customer/*') && $request->has('embed')) {
            return 'SAMEORIGIN';
        }
        return 'DENY';
    }

    private function getCacheControl(Request $request): string
    {
        if ($request->is('admin/*') || $request->is('customer/*') || $request->is('login') || $request->is('register')) {
            return 'no-cache, no-store, must-revalidate, private';
        }
        return 'public, max-age=300';
    }

    private function isSuspiciousRequest(Request $request): bool
    {
        $suspiciousPatterns = [
            '/\<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/\.\.\//',
            '/\.\.\\\/',
            '/union\s+select/i',
            '/\' or \'/i',
            '/\' union /i'
        ];

        $checkValues = array_merge(
            $request->query->all(),
            $request->request->all(),
            [$request->getPathInfo(), $request->userAgent()]
        );

        foreach ($checkValues as $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
