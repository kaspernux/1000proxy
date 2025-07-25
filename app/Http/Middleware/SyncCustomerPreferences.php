<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;   

class SyncCustomerPreferences
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('customer')->check()) {
            $user = auth('customer')->user();
            session()->put('locale', $user->locale ?? config('app.locale'));
            session()->put('theme_mode', $user->theme_mode ?? 'system');
            app()->setLocale(session('locale'));
        }

        return $next($request);
    }

}
