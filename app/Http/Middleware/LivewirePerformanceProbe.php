<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewirePerformanceProbe
{
    public function handle(Request $request, Closure $next)
    {
        if (!str_starts_with($request->path(), 'admin') || !str_contains($request->header('Accept',''), 'text/html')) {
            return $next($request);
        }

        $start = microtime(true);
        $response = $next($request);
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        if ($durationMs > 1500) {
            Log::warning('Admin dashboard slow response', [
                'path' => $request->path(),
                'ms' => $durationMs,
                'memory_mb' => round(memory_get_peak_usage(true)/1048576, 1),
                'user_id' => optional($request->user())->id,
            ]);
        }
        return $response;
    }
}
