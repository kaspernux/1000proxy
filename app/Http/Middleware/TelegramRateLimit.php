<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TelegramRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract chat_id from the request for per-chat rate limiting
        $update = $request->all();
        $chatId = $update['message']['chat']['id'] ??
                  $update['callback_query']['message']['chat']['id'] ??
                  'unknown';

        // Rate limiting key
        $key = 'telegram_bot:' . $chatId;

        // Allow 10 requests per minute per chat
        $maxAttempts = 10;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Telegram rate limit exceeded', [
                'chat_id' => $chatId,
                'ip' => $request->ip(),
                'available_in' => $seconds
            ]);

            // Don't send error message to avoid spam - just return OK
            return response('OK', 200);
        }

        // Increment the rate limiter
        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
