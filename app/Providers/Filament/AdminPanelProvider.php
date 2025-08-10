<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages; // still needed for other defaults
use App\Filament\Pages\AdminDashboard;
use App\Filament\Admin\Pages\AnalyticsDashboard; // register analytics page explicitly
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
use App\Filament\Widgets\AdminChartsWidget;
use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\EnhancedPerformanceStatsWidget;
use App\Filament\Widgets\AdminMonitoringWidget;
use App\Filament\Widgets\UserActivityMonitoringWidget;
use Livewire\Livewire;
use App\Http\Middleware\LivewirePerformanceProbe;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
    // Temporary compatibility alias: map removed legacy component slug to new unified widget
    // to avoid stale Livewire snapshot errors causing blank dashboard renders.
    Livewire::component('app.filament.widgets.admin-stats-overview', AdminDashboardStatsWidget::class);
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
            // Expand dashboard/content width beyond the default 7xl container.
            // This removes the inherent max-w-7xl wrapper so widgets can span the full viewport.
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                AdminDashboard::class,
                AnalyticsDashboard::class,
            ])
            ->widgets([
                // Unified primary stats overview
                AdminDashboardStatsWidget::class,
                // Infrastructure & health
                InfrastructureHealthWidget::class,
                // Charts & trends
                AdminChartsWidget::class,
                // Recent activity / operational monitoring
                LatestOrdersWidget::class,
                EnhancedPerformanceStatsWidget::class,
                AdminMonitoringWidget::class,
                UserActivityMonitoringWidget::class,
            ])
            ->bootUsing(function(){
                // Register custom chart dataset persistence plugin
                FilamentAsset::register([
                    Js::make('chart-dataset-persistence', Vite::asset('resources/js/filament-chart-dataset-persistence.js'))->module(),
                ]);
            })
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
                LivewirePerformanceProbe::class, // log slow admin renders
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}