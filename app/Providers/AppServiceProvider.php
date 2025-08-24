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
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    // Avoid heavy config mutations during register; normalization will happen in boot

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
        // Ensure Livewire component aliases used by Filament widgets are globally registered
        // so that POST /livewire/update requests can resolve components even outside panel routes.
        try {
            Livewire::component('app.filament.widgets.admin-stats-overview', \App\Filament\Widgets\AdminDashboardStatsWidget::class);
            Livewire::component('app.filament.admin.widgets.order-metrics-widget', \App\Filament\Admin\Widgets\OrderMetricsWidget::class);
            Livewire::component('app.filament.admin.widgets.user-growth-widget', \App\Filament\Admin\Widgets\UserGrowthWidget::class);
            Livewire::component('app.filament.widgets.user-growth-widget', \App\Filament\Admin\Widgets\UserGrowthWidget::class); // fallback prefix
            // Proactive: chart variant sometimes appears in snapshots
            if (class_exists(\App\Filament\Admin\Widgets\UserGrowthChartWidget::class)) {
                Livewire::component('app.filament.admin.widgets.user-growth-chart-widget', \App\Filament\Admin\Widgets\UserGrowthChartWidget::class);
            }

            // Auto-register all widgets in app/Filament/**/Widgets
            $widgetDirs = [
                app_path('Filament/Widgets'),
                app_path('Filament/Admin/Widgets'),
            ];

            foreach ($widgetDirs as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->getExtension() !== 'php') {
                        continue;
                    }

                    $filePath = $fileInfo->getPathname();
                    $relative = Str::after($filePath, app_path() . DIRECTORY_SEPARATOR);
                    $class = 'App\\' . str_replace([
                        DIRECTORY_SEPARATOR,
                        '.php',
                    ], [
                        '\\',
                        '',
                    ], $relative);

                    if (! class_exists($class)) {
                        continue;
                    }

                    $afterFilament = Str::after($class, 'App\\Filament\\');
                    $segments = explode('\\', $afterFilament);
                    $nsParts = array_filter(array_map(function ($seg) {
                        return Str::kebab($seg);
                    }, array_slice($segments, 0, -1)));
                    $alias = 'app.filament';
                    if (! empty($nsParts)) {
                        $alias .= '.' . implode('.', $nsParts);
                    }
                    $alias .= '.' . Str::kebab(class_basename($class));

                    try {
                        Livewire::component($alias, $class);
                    } catch (\Throwable $e) {
                        // ignore classes that are not Livewire-compatible
                    }
                }
            }
        } catch (\Throwable $e) {
            // Do not block app boot if Livewire isn't ready in some contexts
        }
    // Defensive: if any legacy code calls app('env'), bind it to the environment name (post-boot)
        if (!$this->app->bound('env')) {
            $this->app->bind('env', function () {
                return config('app.env');
            });
        }

        // Normalize mailer configuration after boot so container services are available
        try {
            $defaultMailer = config('mail.default');
            $supported = array_keys(config('mail.mailers', []));
            if ($defaultMailer && !in_array($defaultMailer, $supported, true)) {
                config(['mail.default' => app()->environment('production') ? 'smtp' : 'log']);
            }
        } catch (\Throwable $e) {
            // swallow; config not ready
        }

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

        // Add helpful Livewire testing macros expected by our test suite
        if (class_exists(\Livewire\Features\SupportTesting\Testable::class)) {
            \Livewire\Features\SupportTesting\Testable::macro('assertNull', function (string $name) {
                \PHPUnit\Framework\Assert::assertNull($this->get($name));
                return $this;
            });
            \Livewire\Features\SupportTesting\Testable::macro('assertNotNull', function (string $name) {
                \PHPUnit\Framework\Assert::assertNotNull($this->get($name));
                return $this;
            });
        }

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

    // Activity logging: auth events for User and Customer guards
    Event::listen(Login::class, function (Login $event) {
        try {
            app(\App\Services\ActivityLogger::class)->log('login', $event->user, [
                'guard' => $event->guard,
            ]);
        } catch (\Throwable $e) { /* ignore */ }
    });

    Event::listen(Logout::class, function (Logout $event) {
        try {
            app(\App\Services\ActivityLogger::class)->log('logout', $event->user, [
                'guard' => $event->guard,
            ]);
        } catch (\Throwable $e) { /* ignore */ }
    });

    Event::listen(PasswordReset::class, function (PasswordReset $event) {
        try {
            app(\App\Services\ActivityLogger::class)->log('password_reset', $event->user);
        } catch (\Throwable $e) { /* ignore */ }
    });
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
