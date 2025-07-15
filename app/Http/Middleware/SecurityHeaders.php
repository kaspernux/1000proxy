<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
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

        // Enhanced Content Security Policy
        $csp = $this->buildContentSecurityPolicy($request);

        // Comprehensive Security Headers
        $headers = [
            // Content Security Policy
            'Content-Security-Policy' => $csp,

            // XSS Protection
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => $this->getFrameOptions($request),

            // HTTPS Strict Transport Security
            'Strict-Transport-Security' => $request->isSecure()
                ? 'max-age=31536000; includeSubDomains; preload'
                : '',

            // Referrer Policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Enhanced Permissions Policy
            'Permissions-Policy' => implode(', ', [
                'camera=()',
                'microphone=()',
                'geolocation=(self)',
                'payment=(self)',
                'usb=()',
                'magnetometer=()',
                'accelerometer=()',
                'gyroscope=()',
                'bluetooth=()',
                'ambient-light-sensor=()'
            ]),

            // Cross-Origin Policies
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            'Cross-Origin-Resource-Policy' => 'same-origin',

            // Cache Control for sensitive pages
            'Cache-Control' => $this->getCacheControl($request),
            'Pragma' => 'no-cache',
            'Expires' => '0',

            // Feature Policy (deprecated but still used by some browsers)
            'Feature-Policy' => "camera 'none'; microphone 'none'; geolocation 'self'"
        ];

        // Apply headers to response
        foreach ($headers as $key => $value) {
            if (!empty($value)) {
                $response->headers->set($key, $value);
            }
        }

        // Remove sensitive headers that might leak information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Build Content Security Policy based on request context
     */
    private function buildContentSecurityPolicy(Request $request): string
    {
        $policies = [
            "default-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests"
        ];

        if ($request->is('admin/*') || $request->is('filament/*')) {
            // Admin panel specific policies
            $policies = array_merge($policies, [
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' wss: ws:",
                "frame-ancestors 'none'"
            ]);
        } elseif ($request->is('customer/*')) {
            // Customer panel specific policies
            $policies = array_merge($policies, [
                "script-src 'self' 'unsafe-inline' https://js.stripe.com https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data: https:",
                "connect-src 'self' https://api.stripe.com https://api.nowpayments.io wss: ws:",
                "frame-src 'self' https://js.stripe.com",
                "frame-ancestors 'self'"
            ]);
        } else {
            // Public pages
            $policies = array_merge($policies, [
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data: https:",
                "connect-src 'self'",
                "frame-ancestors 'none'"
            ]);
        }

        return implode('; ', $policies);
    }

    /**
     * Get frame options based on request context
     */
    private function getFrameOptions(Request $request): string
    {
        if ($request->is('customer/*') && $request->has('embed')) {
            return 'SAMEORIGIN'; // Allow embedding for customer widget
        }
        return 'DENY'; // Default to deny all framing
    }

    /**
     * Get cache control based on request sensitivity
     */
    private function getCacheControl(Request $request): string
    {
        if ($request->is('admin/*') || $request->is('customer/*') || $request->is('login') || $request->is('register')) {
            return 'no-cache, no-store, must-revalidate, private';
        }
        return 'public, max-age=300'; // 5 minutes for public content
    }

    /**
     * Check if request appears suspicious
     */
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
