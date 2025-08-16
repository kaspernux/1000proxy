<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Redirect authenticated admin (web guard) users away from guest-only auth pages
 * to the admin dashboard (/admin). This mirrors the behavior already applied
 * for customers via RedirectIfCustomer but targets the admin guard.
 */
class RedirectIfAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            if ($request->is('login') || $request->is('register') || $request->is('forgot') || $request->routeIs('auth.login')) {
                return redirect('/admin');
            }
        }
        return $next($request);
    }
}
