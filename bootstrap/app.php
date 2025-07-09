<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\RedirectIfCustomer;
use App\Http\Middleware\EnhancedErrorHandling;
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
        $middleware->append(RedirectIfCustomer::class);
        $middleware->append(EnhancedErrorHandling::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

    