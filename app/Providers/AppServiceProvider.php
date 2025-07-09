<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom services
        $this->app->singleton(\App\Services\CacheOptimizationService::class, function ($app) {
            return new \App\Services\CacheOptimizationService();
        });

        $this->app->singleton(\App\Services\QueueOptimizationService::class, function ($app) {
            return new \App\Services\QueueOptimizationService();
        });

        $this->app->singleton(\App\Services\AdvancedAnalyticsService::class, function ($app) {
            return new \App\Services\AdvancedAnalyticsService();
        });

        $this->app->singleton(\App\Services\MonitoringService::class, function ($app) {
            return new \App\Services\MonitoringService(
                $app->make(\App\Services\CacheOptimizationService::class),
                $app->make(\App\Services\QueueOptimizationService::class),
                $app->make(\App\Services\AdvancedAnalyticsService::class)
            );
        });

        $this->app->singleton(\App\Services\CacheService::class, function ($app) {
            return new \App\Services\CacheService();
        });

        $this->app->singleton(\App\Services\XUIService::class, function ($app) {
            return new \App\Services\XUIService(1); // Default server for singleton
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        // Configure rate limiting
        $this->configureRateLimiting();

        if (auth('customer')->check() && request()->isMethod('post')) {
            $user = auth('customer')->user();
            session()->put('locale', $user->locale ?? config('app.locale'));
            session()->put('theme_mode', $user->theme_mode ?? 'system');
            session()->put('filament.dark_mode', match ($user->theme_mode) {
                'dark' => true,
                'light' => false,
                default => null,
            });
        
            app()->setLocale(session('locale'));
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('xui', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}