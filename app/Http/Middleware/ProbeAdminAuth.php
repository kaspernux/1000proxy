<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProbeAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            Log::info('ProbeAdminAuth: before Authenticate', [
                'session_started' => $request->hasSession() && $request->session()->isStarted(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'auth_web' => auth('web')->check(),
                'auth_default' => auth()->check(),
                'user_id' => auth()->id(),
                'role' => optional(auth()->user())->role,
            ]);
        }

        $response = $next($request);

        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            Log::info('ProbeAdminAuth: after Authenticate', [
                'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'location' => method_exists($response, 'headers') ? $response->headers->get('Location') : null,
                'auth_web' => auth('web')->check(),
                'auth_default' => auth()->check(),
                'user_id' => auth()->id(),
                'role' => optional(auth()->user())->role,
            ]);
        }

        return $response;
    }
}
