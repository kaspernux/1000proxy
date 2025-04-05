<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('customer')->check()) {
            // User is a customer, redirect them to the product page
            return redirect('/servers'); // Adjust the redirect route as necessary
        }

        return $next($request);
    }
}
