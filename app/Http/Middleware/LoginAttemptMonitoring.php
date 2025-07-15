<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

class LoginAttemptMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log the attempt before processing
        if ($this->isAuthenticationRequest($request)) {
            $this->logAuthenticationAttempt($request);
        }

        $response = $next($request);

        // Check if authentication was successful or failed
        if ($this->isAuthenticationRequest($request)) {
            $this->processAuthenticationResult($request, $response);
        }

        return $response;
    }

    /**
     * Check if this is an authentication request
     */
    private function isAuthenticationRequest(Request $request): bool
    {
        return $request->is('login') ||
               $request->is('admin/login') ||
               $request->is('customer/login') ||
               $request->is('filament/admin/login') ||
               $request->is('filament/customer/login') ||
               ($request->isMethod('POST') && $request->has(['email', 'password']));
    }

    /**
     * Log authentication attempt
     */
    private function logAuthenticationAttempt(Request $request): void
    {
        $logData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $request->input('email'),
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'real_ip' => $request->header('X-Real-IP')
        ];

        // Check for suspicious patterns
        if ($this->isSuspiciousAttempt($request)) {
            Log::warning('Suspicious login attempt detected', $logData);
            $this->recordSuspiciousActivity($request);
        } else {
            Log::info('Login attempt logged', $logData);
        }

        // Store attempt data for rate limiting and analysis
        $this->storeAttemptData($request);
    }

    /**
     * Process the result of an authentication attempt
     */
    private function processAuthenticationResult(Request $request, Response $response): void
    {
        $isSuccessful = $this->isSuccessfulAuthentication($response);
        $email = $request->input('email');
        $ip = $request->ip();

        if ($isSuccessful) {
            $this->handleSuccessfulLogin($email, $ip);
        } else {
            $this->handleFailedLogin($email, $ip, $request);
        }
    }

    /**
     * Check if authentication was successful
     */
    private function isSuccessfulAuthentication(Response $response): bool
    {
        // Check for redirect to dashboard or success status
        return $response->isRedirection() &&
               ($response->headers->get('Location') !== null) &&
               (!str_contains($response->headers->get('Location'), 'login'));
    }

    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin(string $email, string $ip): void
    {
        Log::info('Successful login', [
            'email' => $email,
            'ip' => $ip,
            'timestamp' => now()->toISOString()
        ]);

        // Clear failed attempts for this email/IP
        $this->clearFailedAttempts($email, $ip);

        // Store successful login event
        $this->storeSuccessfulLogin($email, $ip);
    }

    /**
     * Handle failed login
     */
    private function handleFailedLogin(string $email, string $ip, Request $request): void
    {
        Log::warning('Failed login attempt', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        // Increment failed attempts
        $this->incrementFailedAttempts($email, $ip);

        // Check if account should be locked
        $this->checkForAccountLockout($email, $ip);
    }

    /**
     * Check if this appears to be a suspicious attempt
     */
    private function isSuspiciousAttempt(Request $request): bool
    {
        $suspiciousIndicators = [
            // Multiple rapid attempts from same IP
            $this->hasRapidAttempts($request->ip()),

            // Suspicious user agent
            $this->hasSuspiciousUserAgent($request->userAgent()),

            // Common attack patterns in email
            $this->hasAttackPatternsInEmail($request->input('email', '')),

            // Tor or proxy detection
            $this->isPotentialProxy($request),

            // Unusual request patterns
            $this->hasUnusualRequestPatterns($request)
        ];

        return in_array(true, $suspiciousIndicators);
    }

    /**
     * Check for rapid attempts from same IP
     */
    private function hasRapidAttempts(string $ip): bool
    {
        $key = "login_attempts:ip:{$ip}:last_minute";
        $attempts = Cache::get($key, 0);
        return $attempts > 10; // More than 10 attempts per minute
    }

    /**
     * Check for suspicious user agent
     */
    private function hasSuspiciousUserAgent(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/script/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for attack patterns in email
     */
    private function hasAttackPatternsInEmail(string $email): bool
    {
        $attackPatterns = [
            '/\<script/i',
            '/javascript:/i',
            '/\' or \'/i',
            '/union select/i',
            '/\.\.\//i',
            '/<[^>]*>/i' // HTML tags
        ];

        foreach ($attackPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request is coming from potential proxy
     */
    private function isPotentialProxy(Request $request): bool
    {
        $proxyHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_VIA'
        ];

        foreach ($proxyHeaders as $header) {
            if ($request->server($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for unusual request patterns
     */
    private function hasUnusualRequestPatterns(Request $request): bool
    {
        // Check for missing common headers
        $commonHeaders = ['accept', 'accept-language', 'accept-encoding'];
        $missingHeaders = 0;

        foreach ($commonHeaders as $header) {
            if (!$request->hasHeader($header)) {
                $missingHeaders++;
            }
        }

        return $missingHeaders >= 2; // Missing 2 or more common headers
    }

    /**
     * Store attempt data for analysis
     */
    private function storeAttemptData(Request $request): void
    {
        $ip = $request->ip();
        $email = $request->input('email', '');

        // Increment counters
        $this->incrementCounter("login_attempts:ip:{$ip}:last_minute", 60);
        $this->incrementCounter("login_attempts:ip:{$ip}:last_hour", 3600);

        if (!empty($email)) {
            $this->incrementCounter("login_attempts:email:{$email}:last_hour", 3600);
        }
    }

    /**
     * Record suspicious activity
     */
    private function recordSuspiciousActivity(Request $request): void
    {
        $key = "suspicious_activity:" . $request->ip();
        $data = Cache::get($key, []);

        $data[] = [
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'email' => $request->input('email'),
            'user_agent' => $request->userAgent()
        ];

        // Keep only last 50 suspicious activities
        if (count($data) > 50) {
            $data = array_slice($data, -50);
        }

        Cache::put($key, $data, now()->addDay());
    }

    /**
     * Increment failed attempts counter
     */
    private function incrementFailedAttempts(string $email, string $ip): void
    {
        $this->incrementCounter("failed_attempts:email:{$email}", 3600); // 1 hour
        $this->incrementCounter("failed_attempts:ip:{$ip}", 3600); // 1 hour
    }

    /**
     * Clear failed attempts
     */
    private function clearFailedAttempts(string $email, string $ip): void
    {
        Cache::forget("failed_attempts:email:{$email}");
        Cache::forget("failed_attempts:ip:{$ip}");
    }

    /**
     * Store successful login
     */
    private function storeSuccessfulLogin(string $email, string $ip): void
    {
        $key = "successful_logins:{$email}";
        $logins = Cache::get($key, []);

        $logins[] = [
            'ip' => $ip,
            'timestamp' => now()->toISOString()
        ];

        // Keep only last 10 successful logins
        if (count($logins) > 10) {
            $logins = array_slice($logins, -10);
        }

        Cache::put($key, $logins, now()->addWeek());
    }

    /**
     * Check for account lockout conditions
     */
    private function checkForAccountLockout(string $email, string $ip): void
    {
        $emailFailures = Cache::get("failed_attempts:email:{$email}", 0);
        $ipFailures = Cache::get("failed_attempts:ip:{$ip}", 0);

        // Lock account if too many failures
        if ($emailFailures >= 5) {
            $this->lockAccount($email, 'Too many failed attempts');
        }

        // Block IP if too many failures
        if ($ipFailures >= 10) {
            $this->blockIP($ip, 'Too many failed login attempts');
        }
    }

    /**
     * Lock user account
     */
    private function lockAccount(string $email, string $reason): void
    {
        Cache::put("locked_account:{$email}", true, now()->addHour());

        Log::critical('Account locked due to failed login attempts', [
            'email' => $email,
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Block IP address
     */
    private function blockIP(string $ip, string $reason): void
    {
        Cache::put("blocked_ip:{$ip}", true, now()->addHour());

        Log::critical('IP blocked due to failed login attempts', [
            'ip' => $ip,
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Increment a counter with TTL
     */
    private function incrementCounter(string $key, int $ttl): void
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addSeconds($ttl));
    }
}
