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
use App\Console\Commands\TelegramSetBranding;
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
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
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
        TelegramTestBot::class,
        TelegramWebhookInfo::class,
    TelegramSmokeProfile::class,
        // Analytics / reports
        GenerateAnalyticsReport::class,
        HealthCheckCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new PruneOldExportsJob())->dailyAt('02:15');
    // Ensure Horizon collects metrics snapshots (Laravel 12 app.php replaces Console Kernel)
    $schedule->command('horizon:snapshot')->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware) {
    // Global & security middleware
    $middleware->append(SessionSecurity::class);

        // Existing middleware
    $middleware->append(RedirectIfCustomer::class);
    $middleware->append(RedirectIfAdmin::class);
    $middleware->append(EnhancedErrorHandling::class);
    $middleware->append(MobileAnalyticsMiddleware::class);
    $middleware->append(TestMobileEnhancementsMiddleware::class);

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
        if (!app()->environment('testing')) {
            $middleware->web(replace: [
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => EnhancedCsrfProtection::class,
            ]);
        }

        // Relax session cookie for testing so redirects with session errors work reliably
        if (app()->environment('testing')) {
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

