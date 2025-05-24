<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocaleFromSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 🧠 If the user is a logged-in customer
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            // 💬 Set app locale based on customer
            $locale = $customer->locale ?? config('app.locale');
            session()->put('locale', $locale);
            App::setLocale($locale);

            // 🌓 Set dark mode from customer preference
            session()->put('filament.dark_mode', $customer->dark_mode ?? false);
        } else {
            // ⛔️ Fallbacks if not authenticated
            if (session()->has('locale')) {
                App::setLocale(session('locale'));
            }

            if (! session()->has('filament.dark_mode')) {
                session()->put('filament.dark_mode', false);
            }
        }

        return $next($request);
    }
}
