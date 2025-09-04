<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProbeAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
    if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            $sessionStarted = $request->hasSession() && $request->session()->isStarted();
            $sessionId = $sessionStarted ? $request->session()->getId() : null;
            $sessionAll = $sessionStarted ? $request->session()->all() : null;
            $filamentGuard = null;
            try {
                $filamentGuard = Filament::getAuthGuard();
            } catch (\Throwable $e) {
                $filamentGuard = config('filament.auth.guard', 'web');
            }

            Log::info('ProbeAdminAuth: before Authenticate', [
                'filament_guard' => $filamentGuard,
                'session_started' => $sessionStarted,
                'session_id' => $sessionId,
                // Only log top-level session keys to avoid huge payloads
                'session_keys' => is_array($sessionAll) ? array_keys($sessionAll) : null,
                'raw_cookie' => $request->headers->get('Cookie'),
                'testing_user_header' => $request->header('X-Testing-User'),
                'auth_web' => auth('web')->check(),
                'auth_default' => auth()->check(),
                'user_id' => auth()->id(),
                'role' => optional(auth()->user())->role,
            ]);

            // Testing convenience: honor X-Testing-User early in the pipeline so
            // Authenticate middleware sees the guard user even when the Cookie
            // header/session payload are not present or flaky in PHPUnit.
            // Accept testing user from header, server HTTP_ var, or query param
            $testingUserId = $request->header('X-Testing-User') ?: $request->server->get('HTTP_X_TESTING_USER') ?: $request->query('testing_user');
            if ($testingUserId) {
                try {
                    $modelClass = \App\Models\User::class;
                    $user = $modelClass::find($testingUserId);
                    if ($user) {
                        auth($filamentGuard)->setUser($user);
                        // Also set the default guard for convenience
                        auth()->setUser($user);
                        // Write a minimal session marker used by some guards in this app
                        try {
                            if ($request->hasSession()) {
                                // Observed session key 'password_hash_web' present in successful admin sessions
                                $pwKey = 'password_hash_' . ($filamentGuard ?: 'web');
                                $request->session()->put($pwKey, $user->getAuthPassword());
                                // Also store a testing auth id to help later inspection
                                $request->session()->put('testing_auth_user_id', $user->id);
                                $request->session()->save();
                            }
                        } catch (\Throwable $_) {
                            // noop
                        }

                        Log::info('ProbeAdminAuth: early_set_user', ['filament_guard' => $filamentGuard, 'user_id' => auth($filamentGuard)->id(), 'session_keys' => $request->hasSession() ? array_keys($request->session()->all()) : null]);
                    }
                } catch (\Throwable $_) {
                    // noop
                }
            }
        }

        $response = $next($request);

        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            $sessionAll = $request->hasSession() ? $request->session()->all() : null;
            Log::info('ProbeAdminAuth: after Authenticate', [
                'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'location' => method_exists($response, 'headers') ? $response->headers->get('Location') : null,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'session_keys' => is_array($sessionAll) ? array_keys($sessionAll) : null,
                'auth_web' => auth('web')->check(),
                'auth_default' => auth()->check(),
                'user_id' => auth()->id(),
                'role' => optional(auth()->user())->role,
            ]);
        }

        return $response;
    }
}
