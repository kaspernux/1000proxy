<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('/admin/login');
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect('/admin/login')->with('error', 'Your account has been deactivated.');
        }

        // If no specific roles are required, allow any staff role
        if (empty($roles)) {
            $allowedRoles = ['admin', 'support_manager', 'sales_support'];
        } else {
            $allowedRoles = $roles;
        }

        // Check if user has one of the required roles
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'Insufficient permissions for this action.');
        }

        // Update last login time if user is a User model instance
        if ($user instanceof \App\Models\User) {
            $user->update(['last_login_at' => now()]);
        }

        return $next($request);
    }
}
