<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfCustomer
{
    public function handle(Request $request, Closure $next)
    {
        // Never redirect for API routes or JSON/AJAX expectations – these should return JSON not 302.
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
            return $next($request);
        }

        if (Auth::guard('customer')->check()) {
            // If an admin (web guard) is authenticated, do not block admin routes.
            // This prevents scenarios where a user is logged in as a customer and then
            // tries to access the admin panel with valid admin credentials.
            if (Auth::guard('web')->check()) {
                return $next($request);
            }
            // Always allow admin authentication pages so staff can sign in even if a customer session exists.
            if (
                $request->is('admin/login') ||
                $request->is('admin/password-reset') ||
                $request->is('admin/password-reset/*') ||
                $request->is('admin/forgot-password') ||
                $request->is('admin/forgot-password/*') ||
                // Allow admin logout so staff can sign out even if a customer session exists
                ($request->is('admin/logout') && $request->isMethod('post'))
            ) {
                return $next($request);
            }

            // When a customer hits other admin panel areas, respond with 403 per tests.
            if ($request->is('admin') || $request->is('admin/*')) {
                try { \Log::debug('RedirectIfCustomer: aborting with 403 because customer guard present', ['path' => $request->path()]); } catch (\Throwable $_) {}
                abort(403);
            }
            // Allow access to customer panel and related routes
            if ($request->is('customer') || $request->is('customer/*') || $request->is('account') || $request->is('account/*')) {
                return $next($request);
            }
            // Otherwise send them to storefront landing.
            try { \Log::debug('RedirectIfCustomer: redirecting customer to storefront', ['path' => $request->path()]); } catch (\Throwable $_) {}
            return redirect('/servers');
        }

        return $next($request);
    }
}
