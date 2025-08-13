<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
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
use Illuminate\Support\Facades\Gate;
use App\Models\{Order, Server, PaymentMethod, Customer, ServerPlan, ServerClient, Invoice};
use App\Policies\{OrderPolicy, ServerPolicy, PaymentMethodPolicy, CustomerPolicy, ServerPlanPolicy, ServerClientPolicy, InvoicePolicy};
use App\Models\ActivityLog;
use App\Policies\ActivityLogPolicy;


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

        // XUIService is not bound as singleton since it needs different Server instances
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register all Blade components in livewire/components
        $components = [
            'custom-icon', 'xui-server-selector', 'xui-server-browser', 'xui-inbound-manager',
            'accessibility-enhancements', 'advanced-interaction-patterns-demo', 'advanced-layout-demo',
            'advanced-state-demo', 'animated-toggle', 'api-integration-demo', 'app-layout',
            'application-logo', 'client-configuration-builder', 'client-usage-analyzer',
            'color-theme-settings', 'crypto-payment-monitor', 'custom-dropdown', 'data-table',
            'dropdown-link', 'dropdown', 'icon', 'inbound-traffic-monitor', 'interactive-data-table',
            'live-order-tracker', 'livewire-framework-demo', 'nav-link', 'payment-history-table',
            'payment-integration', 'payment-processor', 'proxy-configuration-card', 'pwa-meta',
            'pwa-status', 'responsive-nav-link', 'server-browser', 'server-status-monitor',
            'telegram-integration', 'telegram-notification-center', 'theme-switcher',
            'ui-component-library-demo', 'websocket-demo', 'xui-additional-components',
            'xui-connection-tester', 'xui-health-monitor', 'xui-integration-interface',
            'xui-integration',
        ];
        foreach ($components as $component) {
            Blade::component('livewire.components.' . $component, $component);
        }

        // Register Blade components in livewire/components/forms
        $formComponents = [
            'auto-complete-search', 'dynamic-form-validation', 'file-upload-drag-drop', 'multi-step-wizard',
        ];
        foreach ($formComponents as $component) {
            Blade::component('livewire.components.forms.' . $component, $component);
        }

        // Register Blade components in livewire/components/ui
        $uiComponents = [
            'dashboard-chart', 'date-picker', 'dropdown', 'file-upload', 'modal', 'progress',
            'theme-switcher', 'toast', 'toggle',
        ];
        foreach ($uiComponents as $component) {
            Blade::component('livewire.components.ui.' . $component, $component);
        }

        Model::unguard();

    // Register policies (centralized until AuthServiceProvider is added)
    Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
    Gate::policy(Order::class, OrderPolicy::class);
    Gate::policy(Server::class, ServerPolicy::class);
    Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);
    Gate::policy(Customer::class, CustomerPolicy::class);
    Gate::policy(ServerPlan::class, ServerPlanPolicy::class);
    Gate::policy(ServerClient::class, ServerClientPolicy::class);
    Gate::policy(Invoice::class, InvoicePolicy::class);
    Gate::policy(ActivityLog::class, ActivityLogPolicy::class);

        // Configure rate limiting
        $this->configureRateLimiting();

        // Share mobileClasses across all views if not set
        View::composer('*', function($view) {
            if (!array_key_exists('mobileClasses', $view->getData())) {
                $ua = request()->userAgent();
                $classes = ['device-mobile'];
                if ($ua && preg_match('/iPad|Tablet/i', $ua)) { $classes = ['device-tablet']; }
                if ($ua && preg_match('/Android 4|SM-G355H/i', $ua)) { $classes[] = 'performance-low'; }
                $view->with('mobileClasses', $classes);
            }
        });

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

    // Inline event listeners (no EventServiceProvider present)
    Event::listen(\App\Events\OrderPaid::class, \App\Listeners\DispatchProvisioningOnOrderPaid::class);
    Event::listen(\App\Events\OrderProvisioned::class, \App\Listeners\SendOrderProvisionedNotification::class);
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
