<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class EnhancedCsrfProtection extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
    'webhooks/*',
    'api/public/*',
    'stripe/webhook',
    'paypal/webhook',
    'nowpayments/webhook',
    'telegram/webhook*',
    'livewire/update',
    'livewire/upload-file',
    'livewire/*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // In testing, disable CSRF enforcement to allow feature tests focusing on validation/session
        if (app()->environment('testing')) {
            return $next($request);
        }
        // Skip CSRF for specific conditions
        if ($this->shouldSkipCsrfCheck($request)) {
            return $next($request);
        }

        // Log CSRF token validation attempts
        if ($this->isReading($request) || $this->runningUnitTests() || $this->inExceptArray($request) || $this->tokensMatch($request)) {
            return $this->addTokenToResponse($request, $next($request));
        }

        // Log CSRF failures for security monitoring
        $this->logCsrfFailure($request);

        // Check for potential CSRF attack patterns
        if ($this->isPotentialCsrfAttack($request)) {
            $this->handleCsrfAttack($request);
        }

        throw new \Illuminate\Session\TokenMismatchException('CSRF token validation failed.');
    }

    /**
     * Determine if CSRF check should be skipped
     */
    protected function shouldSkipCsrfCheck(Request $request): bool
    {
        // Skip for API requests with valid API key
        if ($request->is('api/*') && $this->hasValidApiKey($request)) {
            return true;
        }

        // Skip for webhook endpoints with valid signatures
        if ($this->isValidWebhook($request)) {
            return true;
        }

        // Skip for AJAX requests from same origin with valid referrer
        if ($this->isValidSameOriginAjax($request)) {
            return true;
        }

        return false;
    }

    /**
     * Check if request has valid API key
     */
    protected function hasValidApiKey(Request $request): bool
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return false;
        }

        // Validate API key format and existence
        if (strlen($apiKey) !== 32 || !ctype_alnum($apiKey)) {
            return false;
        }

        // Check if API key exists and is valid (implement your own logic)
        return Cache::remember("api_key_valid:{$apiKey}", 300, function() use ($apiKey) {
            // Check against database or cache
            return \App\Models\ApiKey::where('key', $apiKey)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->exists();
        });
    }

    /**
     * Check if this is a valid webhook request
     */
    protected function isValidWebhook(Request $request): bool
    {
        // Accept both exact "/webhook" and secret-suffixed "/webhook/*" paths
        if (!($request->is('*/webhook') || $request->is('*/webhook/*'))) {
            return false;
        }

        // Validate webhook signatures
        if ($request->is('stripe/webhook')) {
            return $this->validateStripeWebhook($request);
        }

        if ($request->is('paypal/webhook')) {
            return $this->validatePayPalWebhook($request);
        }

    if ($request->is('telegram/webhook') || $request->is('telegram/webhook/*')) {
            return $this->validateTelegramWebhook($request);
        }

        return false;
    }

    /**
     * Validate Stripe webhook signature
     */
    protected function validateStripeWebhook(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();
        $secret = config('services.stripe.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        try {
            \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;
        } catch (\Exception $e) {
            Log::warning('Invalid Stripe webhook signature', [
                'ip' => $request->ip(),
                'signature' => $signature,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate PayPal webhook signature
     */
    protected function validatePayPalWebhook(Request $request): bool
    {
        // Implement PayPal webhook validation
        $headers = $request->headers->all();
        $payload = $request->getContent();

        // PayPal webhook validation logic here
        return true; // Simplified for now
    }

    /**
     * Validate Telegram webhook
     */
    protected function validateTelegramWebhook(Request $request): bool
    {
        // Prefer Telegram's secret header validation when configured
        $expected = config('services.telegram.secret_token');
        if ($expected) {
            $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');
            return hash_equals($expected, (string) $provided);
        }

        // Fallback: accept plain /telegram/webhook when no secret is set
        return $request->is('telegram/webhook') || $request->is('telegram/webhook/*');
    }

    /**
     * Check if this is a valid same-origin AJAX request
     */
    protected function isValidSameOriginAjax(Request $request): bool
    {
        if (!$request->ajax()) {
            return false;
        }

        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        $host = $request->getHost();

        // Check if origin or referer matches current host
        if ($origin) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            return $originHost === $host;
        }

        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            return $refererHost === $host;
        }

        return false;
    }

    /**
     * Log CSRF validation failure
     */
    protected function logCsrfFailure(Request $request): void
    {
        $logData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'referer' => $request->header('Referer'),
            'origin' => $request->header('Origin'),
            'session_token' => $request->session()->token(),
            'provided_token' => $request->input('_token') ?? $request->header('X-CSRF-TOKEN'),
            'timestamp' => now()->toISOString()
        ];

        Log::warning('CSRF token validation failed', $logData);

        // Track CSRF failures for potential attack detection
        $this->trackCsrfFailure($request);
    }

    /**
     * Track CSRF failures for security monitoring
     */
    protected function trackCsrfFailure(Request $request): void
    {
        $ip = $request->ip();
        $key = "csrf_failures:{$ip}";

        $failures = Cache::get($key, 0);
        Cache::put($key, $failures + 1, now()->addHour());

        // Alert if too many CSRF failures from same IP
        if ($failures >= 10) {
            Log::critical('Multiple CSRF failures detected - potential attack', [
                'ip' => $ip,
                'failures' => $failures + 1,
                'user_agent' => $request->userAgent(),
                'last_path' => $request->path()
            ]);
        }
    }

    /**
     * Check if this appears to be a CSRF attack
     */
    protected function isPotentialCsrfAttack(Request $request): bool
    {
        // Check for common CSRF attack patterns
        $suspiciousIndicators = [
            // Missing or suspicious referer
            $this->hasSuspiciousReferer($request),

            // Automated request patterns
            $this->isAutomatedRequest($request),

            // Multiple rapid CSRF failures
            $this->hasMultipleCsrfFailures($request),

            // Suspicious user agent
            $this->hasSuspiciousUserAgent($request),
        ];

        return count(array_filter($suspiciousIndicators)) >= 2;
    }

    /**
     * Check for suspicious referer header
     */
    protected function hasSuspiciousReferer(Request $request): bool
    {
        $referer = $request->header('Referer');

        if (!$referer) {
            return true; // Missing referer is suspicious for form submissions
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $currentHost = $request->getHost();

        // External referer for state-changing operations
        return $refererHost !== $currentHost;
    }

    /**
     * Check if request appears automated
     */
    protected function isAutomatedRequest(Request $request): bool
    {
        $userAgent = $request->userAgent();

        if (!$userAgent) {
            return true;
        }

        $botPatterns = [
            '/bot/i', '/crawler/i', '/spider/i', '/curl/i',
            '/wget/i', '/python/i', '/script/i', '/automation/i'
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for multiple CSRF failures from same IP
     */
    protected function hasMultipleCsrfFailures(Request $request): bool
    {
        $key = "csrf_failures:{$request->ip()}";
        return Cache::get($key, 0) >= 5;
    }

    /**
     * Check for suspicious user agent
     */
    protected function hasSuspiciousUserAgent(Request $request): bool
    {
        $userAgent = $request->userAgent();

        // Very short or suspicious user agents
        return !$userAgent || strlen($userAgent) < 10 ||
               strpos($userAgent, '<') !== false ||
               strpos($userAgent, 'script') !== false;
    }

    /**
     * Handle potential CSRF attack
     */
    protected function handleCsrfAttack(Request $request): void
    {
        // Log critical security event
        Log::critical('Potential CSRF attack detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'referer' => $request->header('Referer'),
            'origin' => $request->header('Origin'),
            'timestamp' => now()->toISOString()
        ]);

        // Temporarily block IP for security
        $blockKey = "csrf_attack_block:{$request->ip()}";
        Cache::put($blockKey, true, now()->addMinutes(15));

        // Trigger security alert
        $this->triggerSecurityAlert($request, 'CSRF Attack');
    }

    /**
     * Trigger security alert
     */
    protected function triggerSecurityAlert(Request $request, string $alertType): void
    {
        // Send notification to admins (implement based on your notification system)
        try {
            // Example: Send email or Slack notification
            // Mail::to(config('security.monitoring.admin_notification_email'))
            //     ->send(new SecurityAlertMail($alertType, $request));
        } catch (\Exception $e) {
            Log::error('Failed to send security alert', [
                'alert_type' => $alertType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add CSRF token to response with enhanced security
     */
    protected function addTokenToResponse($request, $response)
    {
        if ($this->shouldAddXsrfTokenCookie()) {
            $response = $this->addCookieToResponse($request, $response);
        }

        // Add security headers for CSRF protection
        if (method_exists($response, 'headers')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
