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
            Log::info('DebugAdminRedirects: request start', [
                'path' => $request->path(),
                'auth_web' => auth('web')->check(),
                'auth_default' => auth()->check(),
                'user_id' => auth()->id(),
                'role' => optional(auth()->user())->role,
            ]);
        }

        $response = $next($request);

        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            if (method_exists($response, 'isRedirection') && $response->isRedirection()) {
                Log::info('DebugAdminRedirects: redirect detected', [
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                    'location' => $response->headers->get('Location'),
                    'auth_web' => auth('web')->check(),
                    'auth_default' => auth()->check(),
                    'user_id' => auth()->id(),
                    'role' => optional(auth()->user())->role,
                ]);
            }
        }

        return $response;
    }
}
