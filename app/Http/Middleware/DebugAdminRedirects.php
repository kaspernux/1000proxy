<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugAdminRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            // Avoid starting or touching the session/auth too early in the middleware stack.
            // Read the raw session cookie value (if present) and request cookies, but only
            // call session()/auth() when the request already has a session to prevent
            // accidental session creation which can confuse the request lifecycle.
            $cookies = $request->cookies->all();
            $cookieSessionId = $request->cookies->get(config('session.cookie', session_name()));

            $sessionId = null;
            $sessionKeys = [];
            $authWeb = null;
            $authDefault = null;
            $userId = null;
            $role = null;

            if ($request->hasSession()) {
                try {
                    $sessionId = $request->session()->getId();
                    $sessionKeys = array_keys($request->session()->all());
                } catch (\Throwable $_) {}

                try {
                    $authWeb = auth('web')->check();
                    $authDefault = auth()->check();
                    $userId = auth()->id();
                    $role = optional(auth()->user())->role;
                } catch (\Throwable $_) {}
            }

            // Also capture the raw Cookie header for exact string comparison in logs.
            $rawCookieHeader = $request->headers->get('Cookie');
            Log::info('DebugAdminRedirects: request start', [
                'path' => $request->path(),
                'cookie_session_id' => $cookieSessionId,
                'cookies' => $cookies,
                'raw_cookie_header' => $rawCookieHeader,
            ]);
        }

        $response = $next($request);

        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            if (method_exists($response, 'isRedirection') && $response->isRedirection()) {
                // After the response (and after StartSession middleware) it's safer to
                // inspect the session and auth state. Guard calls to avoid exceptions
                // when session or auth are not available.
                $cookies = $request->cookies->all();
                $sessionKeys = [];
                $sessionId = null;
                $authWeb = null;
                $authDefault = null;
                $userId = null;
                $role = null;

                if ($request->hasSession()) {
                    try {
                        $sessionId = $request->session()->getId();
                        $sessionKeys = array_keys($request->session()->all());
                    } catch (\Throwable $_) {}

                    try {
                        $authWeb = auth('web')->check();
                        $authDefault = auth()->check();
                        $userId = auth()->id();
                        $role = optional(auth()->user())->role;
                    } catch (\Throwable $_) {}
                }

                Log::info('DebugAdminRedirects: redirect detected', [
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                    'location' => $response->headers->get('Location'),
                    'session_id' => $sessionId,
                    'cookies' => $cookies,
                    'session_keys' => $sessionKeys,
                    'auth_web' => $authWeb,
                    'auth_default' => $authDefault,
                    'user_id' => $userId,
                    'role' => $role,
                ]);
            }
        }

        return $response;
    }
}
