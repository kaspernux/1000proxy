<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\RedirectIfCustomer;
use App\Http\Middleware\EnhancedErrorHandling;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\LoginAttemptMonitoring;
use App\Http\Middleware\SessionSecurity;
use App\Http\Middleware\EnhancedCsrfProtection;
use App\Http\Middleware\TelegramRateLimit;
use App\Http\Middleware\StaffRoleMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Security Middleware (applied globally)
        // $middleware->append(SecurityHeaders::class); // Disabled to remove CSP header
        $middleware->append(SessionSecurity::class);

        // Existing middleware
        $middleware->append(RedirectIfCustomer::class);
        $middleware->append(EnhancedErrorHandling::class);

        // Named middleware for specific routes
        $middleware->alias([
            'auth.monitoring' => LoginAttemptMonitoring::class,
            'rate.limit' => RateLimitMiddleware::class,
            'csrf.enhanced' => EnhancedCsrfProtection::class,
            'telegram.rate' => TelegramRateLimit::class,
            'staff.role' => StaffRoleMiddleware::class,
        ]);

        // Apply enhanced CSRF protection to web routes
        $middleware->web(append: [
            EnhancedCsrfProtection::class,
        ]);

        // Apply rate limiting to API routes
        $middleware->api(append: [
            RateLimitMiddleware::class . ':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

