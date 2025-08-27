<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\On; // for potential future real-time hooks
use BackedEnum;

class AdminDashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament.pages.admin-dashboard';

    public function getTitle(): string
    {
    return __('Admin Dashboard');
    }

    /**
     * Curated widget list ordering for clarity & narrative flow.
     * New grouping: 
     * 1. KPIs (high-level numbers)
     * 2. Revenue & Growth chart (context for trends)
     * 3. Latest Orders (immediate transactional recency)
     * 4. Recent System Activities (system/order activity stream)
     * 5. Infrastructure Health (platform stability)
     * 6. Performance Stats (deeper internal metrics)
     * 7. User Activity (behavioral / auditing)
     */
    public function getWidgets(): array
    {
        return [
            // Hero flow: KPIs → Trends → Operations → Infra → Performance → Users
            \App\Filament\Widgets\UnifiedOpsOverviewWidget::class,           // Unified KPIs (business + infra)
            \App\Filament\Widgets\AdminChartsWidget::class,                  // Trends
            \App\Filament\Widgets\LatestOrdersWidget::class,                 // Ops recency
            \App\Filament\Widgets\LiveServerMetricsWidget::class,            // Infra live
            \App\Filament\Widgets\AdminMonitoringWidget::class,              // Activity stream
            \App\Filament\Widgets\EnhancedPerformanceStatsWidget::class,     // Deep perf
            //\App\Filament\Widgets\UserActivityMonitoringWidget::class,       // Users
        ];
    }

    /**
     * Custom responsive column layout mapping.
     * Must be public to satisfy Filament\Pages\Dashboard contract.
     *
     * @return array<string,int>|int
     */
    public function getColumns(): array|int
    {
    // Use a responsive 6-col grid; widgets can span via own $columnSpan
    return [ 'default' => 1, 'sm' => 2, 'md' => 6, 'xl' => 6 ];
    }

    /**
     * Mirror Customer dashboard pattern: explicitly expose visible widgets.
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getVisibleWidgets(): array
    {
        return $this->getWidgets();
    }

    /**
     * Extra data passed into widgets component (none currently).
     * @return array<string,mixed>
     */
    public function getWidgetData(): array
    {
        return [];
    }

    /**
     * Dashboard header actions (refresh, clear caches, export, navigate)
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('home')
                ->label(__('Home'))
                ->icon('heroicon-o-home')
                ->color('gray')
                ->url(fn (): string => url('/')),

            Action::make('refresh_dashboard')
                ->label(__('Refresh Metrics'))
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshAllWidgets())
                ->tooltip('Emit Livewire refresh events for all dashboard widgets'),

            Action::make('clear_cache')
                ->label(__('Clear Dashboard Cache'))
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->clearDashboardCaches())
                ->tooltip('Flush cached dashboard metric keys'),

            // Only show analytics link if route exists to avoid RouteNotFoundException
            ...(!Route::has('filament.admin.pages.analytics-dashboard') ? [] : [
                Action::make('view_analytics')
                    ->label(__('Analytics Page'))
                    ->icon('heroicon-m-chart-bar-square')
                    ->color('primary')
                    ->url(route('filament.admin.pages.analytics-dashboard'))
                    ->openUrlInNewTab(),
            ]),
        ];
    }

    private function refreshAllWidgets(): void
    {
        // Dispatch events widgets are listening for (see individual $listeners arrays)
        $this->dispatch('refreshRevenueMetrics');
        $this->dispatch('serverStatusUpdated');
        $this->dispatch('refreshInfrastructureHealth');
        $this->dispatch('orderPaid');
        $this->dispatch('$refresh');
        Notification::make()
            ->title(__('Dashboard metrics refresh triggered.'))
            ->success()
            ->send();
    }

    private function clearDashboardCaches(): void
    {
        $keys = [
            // Revenue / customers
            'dash.revenue.summary','dash.revenue.daily.7','dash.revenue.monthSeries',
            // Server / infra
            'dash.server.summary','dash.server.traffic','dash.connections.trend.12','infra.xui.status','infra.cpu.avg',
            // Chart widget
            'dash.chart.filter.today','dash.chart.filter.week','dash.chart.filter.month','dash.chart.filter.90days','dash.chart.filter.year',
            // Performance widget
            'admin.stats.system_performance','admin.stats.database_performance','admin.stats.cache_performance','admin.stats.api_performance',
        ];
        foreach ($keys as $k) { Cache::forget($k); }
        Notification::make()
            ->title(__('Dashboard caches cleared.'))
            ->success()
            ->send();
        $this->dispatch('$refresh');
    }
}
