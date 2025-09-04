<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TestSessionInspector
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            if ($request->hasSession()) {
                try {
                    $all = $request->session()->all();
                    // Truncate large values to keep logs readable
                    $truncated = array_map(function ($v) {
                        if (is_string($v) && strlen($v) > 200) return substr($v, 0, 200) . '...';
                        return $v;
                    }, $all);

                    Log::info('TestSessionInspector: session payload', [
                        'session_id' => $request->session()->getId(),
                        'keys' => array_keys($all),
                        'snapshot' => $truncated,
                    ]);
                } catch (\Throwable $e) {
                    Log::info('TestSessionInspector: failed to read session', ['err' => $e->getMessage()]);
                }
            } else {
                Log::info('TestSessionInspector: no session', ['path' => $request->path()]);
            }
        }

        return $next($request);
    }
}
