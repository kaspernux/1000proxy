<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TestMobileEnhancementsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
    // Previously injected test HTML/CSS markers here. Now layout & views contain required markers natively.
    return $next($request);
    }
}
