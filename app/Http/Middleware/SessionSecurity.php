<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SessionSecurity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for public routes
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        // Validate session security
        if (Auth::check()) {
            $this->validateSessionSecurity($request);
        }

        // Set secure session settings
        $this->setSecureSessionSettings($request);

        $response = $next($request);

        // Update session tracking
        if (Auth::check()) {
            $this->updateSessionTracking($request);
        }

        return $response;
    }

    /**
     * Check if this is a public route that doesn't need session validation
     */
    private function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'login', 'register', 'password/reset', 'password/email',
            'api/public/*', 'webhooks/*', 'health-check'
        ];

        foreach ($publicRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate session security for authenticated users
     */
    private function validateSessionSecurity(Request $request): void
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();

        // Check session timeout
        $this->checkSessionTimeout($request);

        // Validate IP address if enabled
        if (config('security.session.validate_ip', true)) {
            $this->validateIPAddress($request, $user, $sessionId);
        }

        // Validate user agent if enabled
        if (config('security.session.validate_user_agent', true)) {
            $this->validateUserAgent($request, $user, $sessionId);
        }

        // Check for concurrent sessions
        $this->checkConcurrentSessions($request, $user, $sessionId);

        // Check for session hijacking indicators
        $this->checkSessionHijacking($request, $user, $sessionId);
    }

    /**
     * Check if session has timed out
     */
    private function checkSessionTimeout(Request $request): void
    {
        $timeout = config('security.session.timeout', 120) * 60; // Convert to seconds
        $lastActivity = $request->session()->get('last_activity', time());

        if (time() - $lastActivity > $timeout) {
            Log::info('Session timeout for user', [
                'user_id' => Auth::id(),
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'timeout_minutes' => $timeout / 60
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect to login with timeout message
            redirect()->route('login')->with('message', 'Your session has expired due to inactivity.')->send();
        }

        // Update last activity
        $request->session()->put('last_activity', time());
    }

    /**
     * Validate IP address consistency
     */
    private function validateIPAddress(Request $request, $user, string $sessionId): void
    {
        $currentIP = $request->ip();
        $sessionIP = $request->session()->get('session_ip');

        if (!$sessionIP) {
            // First time setting IP for this session
            $request->session()->put('session_ip', $currentIP);
            return;
        }

        if ($sessionIP !== $currentIP) {
            Log::warning('IP address changed during session', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'original_ip' => $sessionIP,
                'new_ip' => $currentIP,
                'user_agent' => $request->userAgent()
            ]);

            // Alert user of IP change if configured
            if (config('security.ip_security.alert_on_ip_change', true)) {
                $this->alertIPChange($user, $sessionIP, $currentIP);
            }

            // Force re-authentication for sensitive operations
            $this->requireReAuthentication($request);
        }
    }

    /**
     * Validate user agent consistency
     */
    private function validateUserAgent(Request $request, $user, string $sessionId): void
    {
        $currentUserAgent = $request->userAgent();
        $sessionUserAgent = $request->session()->get('session_user_agent');

        if (!$sessionUserAgent) {
            // First time setting user agent for this session
            $request->session()->put('session_user_agent', $currentUserAgent);
            return;
        }

        if ($sessionUserAgent !== $currentUserAgent) {
            Log::warning('User agent changed during session', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'original_user_agent' => $sessionUserAgent,
                'new_user_agent' => $currentUserAgent,
                'ip' => $request->ip()
            ]);

            // This could indicate session hijacking
            $this->handleSuspiciousActivity($request, $user, 'User agent change');
        }
    }

    /**
     * Check for concurrent sessions
     */
    private function checkConcurrentSessions(Request $request, $user, string $sessionId): void
    {
        $activeSessionsKey = "active_sessions:user:{$user->id}";
        $activeSessions = Cache::get($activeSessionsKey, []);

        // Add current session
        $activeSessions[$sessionId] = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now()->toISOString()
        ];

        // Remove expired sessions (older than session lifetime)
        $sessionLifetime = config('session.lifetime', 120) * 60; // Convert to seconds
        $activeSessions = array_filter($activeSessions, function ($session) use ($sessionLifetime) {
            return strtotime($session['last_activity']) > time() - $sessionLifetime;
        });

        // Check session limit
        $maxSessions = config('security.session.max_concurrent_sessions', 3);
        if (count($activeSessions) > $maxSessions) {
            Log::warning('Too many concurrent sessions', [
                'user_id' => $user->id,
                'active_sessions' => count($activeSessions),
                'max_allowed' => $maxSessions
            ]);

            // Invalidate oldest sessions
            $this->invalidateOldestSessions($activeSessions, $maxSessions, $sessionId);
        }

        // Update cache
        Cache::put($activeSessionsKey, $activeSessions, now()->addHours(24));
    }

    /**
     * Check for session hijacking indicators
     */
    private function checkSessionHijacking(Request $request, $user, string $sessionId): void
    {
        $sessionData = $request->session()->all();

        // Check for suspicious session data modifications
        $suspiciousIndicators = [
            // Rapid location changes
            $this->hasRapidLocationChanges($request, $user),

            // Unusual access patterns
            $this->hasUnusualAccessPatterns($request, $user),

            // Suspicious timing
            $this->hasSuspiciousTiming($request, $user),
        ];

        if (count(array_filter($suspiciousIndicators)) >= 2) {
            $this->handleSuspiciousActivity($request, $user, 'Potential session hijacking');
        }
    }

    /**
     * Check for rapid location changes
     */
    private function hasRapidLocationChanges(Request $request, $user): bool
    {
        $locationKey = "user_locations:{$user->id}";
        $locations = Cache::get($locationKey, []);

        $currentLocation = $this->getLocationFromIP($request->ip());

        if (empty($locations)) {
            Cache::put($locationKey, [$currentLocation], now()->addHour());
            return false;
        }

        $lastLocation = end($locations);

        // If locations are very different and time difference is small
        if ($this->getDistanceBetweenLocations($lastLocation, $currentLocation) > 1000 &&
            time() - strtotime($lastLocation['timestamp']) < 3600) { // 1 hour
            return true;
        }

        // Update locations
        $locations[] = $currentLocation;
        if (count($locations) > 10) {
            $locations = array_slice($locations, -10);
        }
        Cache::put($locationKey, $locations, now()->addDay());

        return false;
    }

    /**
     * Check for unusual access patterns
     */
    private function hasUnusualAccessPatterns(Request $request, $user): bool
    {
        $patternKey = "access_patterns:{$user->id}";
        $patterns = Cache::get($patternKey, []);

        $currentHour = date('H');
        $patterns[$currentHour] = ($patterns[$currentHour] ?? 0) + 1;

        // Clean old patterns (keep only last 24 hours)
        if (count($patterns) > 24) {
            $patterns = array_slice($patterns, -24, null, true);
        }

        Cache::put($patternKey, $patterns, now()->addDay());

        // Check if current access is unusual (outside normal hours)
        $normalHours = array_keys(array_filter($patterns, fn($count) => $count >= 5));

        return !empty($normalHours) && !in_array($currentHour, $normalHours);
    }

    /**
     * Check for suspicious timing
     */
    private function hasSuspiciousTiming(Request $request, $user): bool
    {
        $lastRequest = Cache::get("last_request:{$user->id}");

        if (!$lastRequest) {
            Cache::put("last_request:{$user->id}", time(), now()->addHour());
            return false;
        }

        $timeDiff = time() - $lastRequest;
        Cache::put("last_request:{$user->id}", time(), now()->addHour());

        // Suspiciously fast requests (less than 1 second) or very long gaps
        return $timeDiff < 1 || $timeDiff > 3600;
    }

    /**
     * Set secure session settings
     */
    private function setSecureSessionSettings(Request $request): void
    {
        // Ensure secure cookies in production
        if (app()->environment('production')) {
            config(['session.secure' => true]);
            config(['session.http_only' => true]);
            config(['session.same_site' => 'strict']);
        }

        // Regenerate session ID periodically
        if (Auth::check() && !$request->session()->has('last_regeneration')) {
            $request->session()->regenerate();
            $request->session()->put('last_regeneration', time());
        } elseif (Auth::check()) {
            $lastRegeneration = $request->session()->get('last_regeneration', time());
            if (time() - $lastRegeneration > 1800) { // 30 minutes
                $request->session()->regenerate();
                $request->session()->put('last_regeneration', time());
            }
        }
    }

    /**
     * Update session tracking
     */
    private function updateSessionTracking(Request $request): void
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();

        // Track session activity
        $activityKey = "session_activity:{$sessionId}";
        $activity = Cache::get($activityKey, []);

        $activity[] = [
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'ip' => $request->ip()
        ];

        // Keep only last 50 activities
        if (count($activity) > 50) {
            $activity = array_slice($activity, -50);
        }

        Cache::put($activityKey, $activity, now()->addHours(2));
    }

    /**
     * Alert user of IP change
     */
    private function alertIPChange($user, string $oldIP, string $newIP): void
    {
        // Send notification (implement based on your notification system)
        Log::info('Sending IP change alert', [
            'user_id' => $user->id,
            'old_ip' => $oldIP,
            'new_ip' => $newIP
        ]);
    }

    /**
     * Require re-authentication for sensitive operations
     */
    private function requireReAuthentication(Request $request): void
    {
        $request->session()->put('requires_reauth', true);
        $request->session()->put('reauth_reason', 'IP address changed');
    }

    /**
     * Handle suspicious activity
     */
    private function handleSuspiciousActivity(Request $request, $user, string $reason): void
    {
        Log::critical('Suspicious session activity detected', [
            'user_id' => $user->id,
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId()
        ]);

        // Force logout and session invalidation
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Block IP temporarily
        Cache::put("blocked_ip:{$request->ip()}", true, now()->addMinutes(30));
    }

    /**
     * Helper methods for location and distance calculations
     */
    private function getLocationFromIP(string $ip): array
    {
        // Implement IP geolocation (using a service like MaxMind)
        return [
            'ip' => $ip,
            'country' => 'Unknown',
            'city' => 'Unknown',
            'lat' => 0,
            'lon' => 0,
            'timestamp' => now()->toISOString()
        ];
    }

    private function getDistanceBetweenLocations(array $loc1, array $loc2): float
    {
        // Simple distance calculation (implement proper geolocation distance)
        return abs($loc1['lat'] - $loc2['lat']) + abs($loc1['lon'] - $loc2['lon']);
    }

    private function invalidateOldestSessions(array &$sessions, int $maxSessions, string $currentSessionId): void
    {
        // Keep current session and newest sessions
        unset($sessions[$currentSessionId]);

        // Sort by last activity
        uasort($sessions, function ($a, $b) {
            return strtotime($a['last_activity']) <=> strtotime($b['last_activity']);
        });

        // Remove oldest sessions
        while (count($sessions) >= $maxSessions) {
            array_shift($sessions);
        }

        // Add back current session
        $sessions[$currentSessionId] = [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity' => now()->toISOString()
        ];
    }
}
