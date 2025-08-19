<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ForceAdminForActivityLogs
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only trigger on Filament admin activity logs routes
        if ($request->is('admin/activity-logs*')) {
            Log::debug('ForceAdminForActivityLogs: inspecting request', [
                'path' => $request->path(),
                'auth_web' => auth('web')->check(),
                'auth_default' => auth()->check(),
            ]);
            // Resolve the web-guard user explicitly since Filament's auth middleware
            // may not have run yet in this stack position.
            $user = auth('web')->user() ?: auth()->user();

            // If authenticated but not admin, force a 403 to match policy/tests
            if ($user && method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
                Log::info('ForceAdminForActivityLogs: non-admin blocked', [
                    'user_id' => $user->id ?? null,
                    'role' => $user->role ?? null,
                ]);
                abort(403);
            }
        }

        return $next($request);
    }
}
