<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\Widgets\OrderStats;
use App\Http\Middleware\RedirectIfCustomer;
use App\Filament\Widgets\AdminDashboardStatsWidget;
use App\Filament\Widgets\InfrastructureHealthWidget;
use App\Filament\Widgets\RevenueAnalyticsWidget;
use App\Filament\Widgets\AdminChartsWidget;
use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\EnhancedPerformanceStatsWidget;
use App\Filament\Widgets\AdminMonitoringWidget;
use App\Filament\Widgets\UserActivityMonitoringWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->revealablePasswords(false)
            ->profile(isSimple: false)
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                // Unified primary stats overview
                AdminDashboardStatsWidget::class,
                // Infrastructure & health
                InfrastructureHealthWidget::class,
                // Revenue & financial analytics
                RevenueAnalyticsWidget::class,
                // Charts & trends
                AdminChartsWidget::class,
                // Recent activity / operational monitoring
                LatestOrdersWidget::class,
                EnhancedPerformanceStatsWidget::class,
                AdminMonitoringWidget::class,
                UserActivityMonitoringWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectIfCustomer::class, // Redirect Customer to Product page
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}