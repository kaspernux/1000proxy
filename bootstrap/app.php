<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectIfCustomer;
use App\Http\Middleware\RedirectIfAdmin;
use App\Http\Middleware\EnhancedErrorHandling;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\LoginAttemptMonitoring;
use App\Http\Middleware\SessionSecurity;
use App\Http\Middleware\EnhancedCsrfProtection;
use App\Http\Middleware\TelegramRateLimit;
use App\Http\Middleware\StaffRoleMiddleware;
use App\Http\Middleware\MobileAnalyticsMiddleware;
use App\Http\Middleware\TestMobileEnhancementsMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\PruneOldExportsJob;
// Console Commands (migrated from Console Kernel / auto-discovery consolidation)
use App\Console\Commands\SmartRetryQueue;
use App\Console\Commands\TestRealXuiProvisioning;
use App\Console\Commands\SimulateLiveOrders;
use App\Console\Commands\CleanupDedicatedInbounds;
use App\Console\Commands\ConfirmPendingDeposits;
use App\Console\Commands\CreateTestCustomer;
use App\Console\Commands\ExecuteIPRotation;
use App\Console\Commands\GenerateAnalyticsReport;
use App\Console\Commands\HealthCheckCommand;
use App\Console\Commands\LogClearCommand;
use App\Console\Commands\MonitorIntegrations;
use App\Console\Commands\PruneExports;
use App\Console\Commands\QueueMaintenance;
use App\Console\Commands\CacheWarmupCommand;
use App\Console\Commands\RefreshFxRates;
use App\Console\Commands\RefreshServerMetrics;
use App\Console\Commands\RepairXuiSniffing;
use App\Console\Commands\RunCustomerSuccessAutomation;
use App\Console\Commands\SecurityCommand;
use App\Console\Commands\ServerManagementCommand;
use App\Console\Commands\SetupStaffRoles;
use App\Console\Commands\SyncPartnershipData;
use App\Console\Commands\SyncThirdPartyData;
use App\Console\Commands\SystemHealthCheck;
use App\Console\Commands\TelegramSetWebhook;
use App\Console\Commands\TelegramSetCommands;
use App\Console\Commands\CreateProgrammaticOrder;
use App\Console\Commands\LiveCatalogResetAndProvision;
use App\Console\Commands\TelegramSetBranding;
use App\Console\Commands\TelegramPublishBrandingQueued;
use App\Console\Commands\TelegramTestBot;
use App\Console\Commands\TelegramWebhookInfo;
use App\Console\Commands\TelegramSmokeProfile;
use App\Console\Commands\TestFilamentPanels;
use App\Console\Commands\TestHomePage;
use App\Console\Commands\TestLogin;
use App\Console\Commands\TestMail;
use App\Console\Commands\TestPaymentSystem;
use App\Console\Commands\TestTelegramBotIntegration;
use App\Console\Commands\TestUserAuthentication;
use App\Console\Commands\TestXUIService;
use App\Console\Commands\TestXuiEnhancements;
use App\Console\Commands\VerifyQueueWorkers;
use App\Console\Commands\CollectProxyMetrics;
use App\Console\Commands\MonitorSmoke;
use App\Console\Commands\MonitorDebug;
use App\Console\Commands\RefreshClientStatus;
use App\Console\Commands\XuiDiagnoseCommand;
use App\Console\Commands\XuiUnlockCommand;
use App\Console\Commands\XuiConfigurePanel;
use App\Console\Commands\DispatchFeatureAdXuiFetch;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Console\Commands\ReconcileNowPayments;
use App\Console\Commands\DiagnoseOrderProvisioning;
use App\Console\Commands\ReprovisionOrders;



$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::middleware('web')->group(base_path('routes/test-routes.php'));
            // Register PWA routes under the web middleware group
            Route::middleware('web')->group(base_path('routes/pwa.php'));
        },
        health: '/up',
    )
    ->withCommands([
        // Core / maintenance
        SmartRetryQueue::class,
        QueueMaintenance::class,
        VerifyQueueWorkers::class,
        LogClearCommand::class,
        PruneExports::class,
        RefreshFxRates::class,
        CacheWarmupCommand::class,
        // Provisioning & operations
        SimulateLiveOrders::class,
        CleanupDedicatedInbounds::class,
        RepairXuiSniffing::class,
        ExecuteIPRotation::class,
        ServerManagementCommand::class,
        SecurityCommand::class,
        SetupStaffRoles::class,
        RunCustomerSuccessAutomation::class,
        MonitorIntegrations::class,
        SyncPartnershipData::class,
        SyncThirdPartyData::class,
        // Testing & diagnostics
        TestRealXuiProvisioning::class,
        TestXUIService::class,
        TestXuiEnhancements::class,
        TestPaymentSystem::class,
        TestTelegramBotIntegration::class,
        TestUserAuthentication::class,
        TestFilamentPanels::class,
        TestHomePage::class,
        TestLogin::class,
        TestMail::class,
        // Telegram
        TelegramSetWebhook::class,
        TelegramSetCommands::class,
        TelegramSetBranding::class,
        TelegramPublishBrandingQueued::class,
        TelegramTestBot::class,
        TelegramWebhookInfo::class,
        TelegramSmokeProfile::class,
        // Analytics / reports
        GenerateAnalyticsReport::class,
        HealthCheckCommand::class,
        CollectProxyMetrics::class,
        MonitorSmoke::class,
        MonitorDebug::class,
        LiveCatalogResetAndProvision::class,
        CreateProgrammaticOrder::class,
        RefreshClientStatus::class,
        RefreshServerMetrics::class,
        XuiDiagnoseCommand::class,
        XuiUnlockCommand::class,
        XuiConfigurePanel::class,
        DispatchFeatureAdXuiFetch::class,
        ReconcileNowPayments::class,
        DiagnoseOrderProvisioning::class,
        ReprovisionOrders::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new PruneOldExportsJob())->dailyAt('02:15');
    // Ensure Horizon collects metrics snapshots (Laravel 12 app.php replaces Console Kernel)
    $schedule->command('horizon:snapshot')->everyFiveMinutes();
    // Persist client metrics for uptime/history used by Proxy Status Monitoring
    $schedule->command('metrics:collect')->everyFiveMinutes()->withoutOverlapping();
    // Refresh client online flags and live traffic cache periodically
    $schedule->command('clients:refresh-status --limit=800')->everyFiveMinutes()->withoutOverlapping();
    // Warm dashboard/server metrics every 5 minutes; force once hourly.
    $schedule->command('metrics:refresh')->everyFiveMinutes()->withoutOverlapping();
    $schedule->command('metrics:refresh --force')->hourly()->withoutOverlapping();
    // Dispatch feature ad X-UI fetch for active ads every 5 minutes
    $schedule->command('featuread:fetch-xui --only-active')->everyFiveMinutes()->withoutOverlapping();
    // Run XUI client sync every minute (lightweight, only active clients)
    $schedule->command('xui:sync-clients --limit=200')->everyMinute()->withoutOverlapping()->runInBackground();
    $schedule->command('featuread:fetch-xui --only-active')->everyFiveMinutes()->withoutOverlapping();
    // Reconcile NowPayments pending wallet top-ups (run every 10 minutes)
    $schedule->command('nowpayments:reconcile --limit=50')->everyTenMinutes()->withoutOverlapping();

})
    ->withMiddleware(function (Middleware $middleware) {
    // Global & security middleware
    // Testing-only early auth: set the guard user from X-Testing-User before other middleware runs
    if (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing') {
        $middleware->prepend(\App\Http\Middleware\EarlyTestAuth::class);
    }
    $middleware->append(SessionSecurity::class);

        // Existing middleware
    // Intercept admin activity logs requests early to ensure 403 for non-admin roles
    $middleware->append(\App\Http\Middleware\ForceAdminForActivityLogs::class);
    $middleware->append(RedirectIfCustomer::class);
    $middleware->append(RedirectIfAdmin::class);
    $middleware->append(EnhancedErrorHandling::class);
    // Testing-only: log where admin requests are being redirected (to debug 302s)
    $middleware->append(\App\Http\Middleware\DebugAdminRedirects::class);
    $middleware->append(MobileAnalyticsMiddleware::class);
    $middleware->append(TestMobileEnhancementsMiddleware::class);
    // Testing-only: global admin request inspector to log raw headers/cookies
    $middleware->append(\App\Http\Middleware\GlobalAdminRequestInspector::class);

        // Named middleware for specific routes
        $middleware->alias([
            'auth.monitoring' => LoginAttemptMonitoring::class,
            'rate.limit' => RateLimitMiddleware::class,
            'csrf.enhanced' => EnhancedCsrfProtection::class,
            'telegram.rate' => TelegramRateLimit::class,
            'staff.role' => StaffRoleMiddleware::class,
            // Project-specific aliases used throughout routes/tests
            'redirect.customer' => RedirectIfCustomer::class,
            'redirect.admin' => RedirectIfAdmin::class,
        ]);

    // Apply enhanced CSRF protection to web routes (replace default) except in testing to avoid 419 during validation tests
    $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
    if ($appEnv !== 'testing') {
            $middleware->web(replace: [
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => EnhancedCsrfProtection::class,
            ]);
        }

    // Relax session cookie for testing so redirects with session errors work reliably
    if ($appEnv === 'testing') {
            config([
                'session.domain' => null,
                'session.secure' => false,
                'session.same_site' => 'lax',
                'app.url' => 'http://localhost',
            ]);
        }

        // Apply rate limiting to API routes
        $middleware->api(append: [
            RateLimitMiddleware::class . ':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Ensure the container has a concrete 'env' binding early to avoid ReflectionException: Class "env" does not exist
$bootstrapEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
if (!isset($app['env'])) {
    $app->instance('env', $bootstrapEnv);
}

return $app;

