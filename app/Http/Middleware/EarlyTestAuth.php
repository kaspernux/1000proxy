<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EarlyTestAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            $testingUserId = $request->header('X-Testing-User') ?: $request->server->get('HTTP_X_TESTING_USER') ?: $request->query('testing_user');
            if ($testingUserId) {
                try {
                    $modelClass = \App\Models\User::class;
                    $user = $modelClass::find($testingUserId);
                    if ($user) {
                        // Set on web guard and default guard very early
                        try {
                            auth('web')->setUser($user);
                        } catch (\Throwable $_) {}
                        try {
                            auth()->setUser($user);
                        } catch (\Throwable $_) {}
                        // Also set Filament's guard if available so Filament::auth() sees the user
                        try {
                            if (class_exists(\Filament\Facades\Filament::class)) {
                                $filamentGuardName = \Filament\Facades\Filament::getAuthGuard();
                                auth($filamentGuardName)->setUser($user);
                                // also set directly on Filament's auth instance if possible
                                try {
                                    \Filament\Facades\Filament::auth()->setUser($user);
                                } catch (\Throwable $_) {}
                            }
                        } catch (\Throwable $_) {}

                        Log::info('EarlyTestAuth: set_user', ['user_id' => $user->id]);
                    }
                } catch (\Throwable $_) {
                    // noop
                }
            }
        }

        return $next($request);
    }
}
