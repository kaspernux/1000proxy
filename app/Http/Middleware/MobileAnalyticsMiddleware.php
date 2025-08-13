<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AnalyticsEvent;

class MobileAnalyticsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('get') && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $ua = $request->userAgent();
            if ($ua) {
                $deviceType = null;
                if (preg_match('/iPhone|Android|Mobile/i', $ua)) {
                    $deviceType = 'mobile';
                } elseif (preg_match('/iPad|Tablet/i', $ua)) {
                    $deviceType = 'tablet';
                }
                if ($deviceType) {
                    try {
                        AnalyticsEvent::create([
                            'event_type'  => 'page_view',
                            'device_type' => $deviceType,
                            'user_agent'  => substr($ua, 0, 255),
                            'ip'          => $request->ip(),
                            'metadata'    => [ 'path' => $request->path() ],
                        ]);
                    } catch (\Throwable) {
                        // ignore
                    }
                }
            }
        }

        return $response;
    }
}
