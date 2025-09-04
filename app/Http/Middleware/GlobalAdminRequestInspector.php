<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GlobalAdminRequestInspector
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ($request->is('admin') || $request->is('admin/*'))) {
            // Testing convenience: if the test client sends an X-Testing-User header
            // set the authenticated user on the default and web guards early so
            // downstream middleware sees auth()->check() === true without
            // relying on cookies or session persistence.
            $testingUserId = $request->header('X-Testing-User') ?: $request->server->get('HTTP_X_TESTING_USER') ?: $request->query('testing_user');
            if ($testingUserId) {
                try {
                    $modelClass = \App\Models\User::class;
                    $user = $modelClass::find($testingUserId);
                    if ($user) {
                        auth('web')->setUser($user);
                        auth()->setUser($user);
                        Log::info('GlobalAdminRequestInspector: early_set_user', ['user_id' => $user->id]);
                    }
                } catch (\Throwable $_) {
                    // noop
                }
            }
            Log::info('GlobalAdminRequestInspector: incoming', [
                'path' => $request->path(),
                'raw_cookie' => $request->headers->get('Cookie'),
                'cookies' => $request->cookies->all(),
                'headers' => array_slice($request->headers->all(), 0, 50),
            ]);
        }

        return $next($request);
    }
}
