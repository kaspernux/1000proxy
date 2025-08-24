<?php

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Filament\Admin\Resources\BusinessIntelligenceResource;
use App\Services\BusinessIntelligenceService;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;

class BusinessIntelligenceDashboard extends Page
{
    protected static string $resource = BusinessIntelligenceResource::class;

    protected string $view = 'filament.admin.business-intelligence.dashboard';

    protected static ?string $title = 'Business Intelligence Dashboard';

    public array $analytics = [];

    public string $timeRange = '30d'; // 7d | 30d | 90d | 1y

    // Tab state to organize a full-featured view without duplicates
    public string $tab = 'overview'; // overview | revenue | users | servers | insights

    protected function getHeaderWidgets(): array
    {
        return $this->widgetsByTab($this->tab);
    }

    public function getHeader(): ?View
    {
        return view('filament.admin.business-intelligence.header', [
            'timeRange' => $this->timeRange,
            'tab' => $this->tab,
        ]);
    }

    public function mount(): void
    {
        // Load persisted filter state for this user if available
        if (auth()->check()) {
            $state = app(\App\Services\AnalyticsFilterState::class)->get(auth()->id());
            $this->timeRange = $state['time_range'] ?? $this->timeRange;
            $this->tab = $state['bi_tab'] ?? $this->tab;
        }
        $this->loadAnalytics();
    }

    protected function getViewData(): array
    {
        return [
            'analytics' => $this->analytics,
            'timeRange' => $this->timeRange,
            'tab' => $this->tab,
        ];
    }

    protected function loadAnalytics(): void
    {
        $biService = app(BusinessIntelligenceService::class);
        $this->analytics = $biService->getDashboardAnalytics($this->mapRangeToService($this->timeRange));
    }

    public function updatedTimeRange(): void
    {
        if (auth()->check()) {
            app(\App\Services\AnalyticsFilterState::class)->setTimeRange(auth()->id(), $this->timeRange);
        }
        $this->loadAnalytics();
        $this->dispatch('$refresh');
    }

    public function refreshDashboard(): void
    {
        $this->loadAnalytics();
        $this->dispatch('$refresh');
    }

    public function switchTab(string $tab): void
    {
        $valid = ['overview','revenue','users','servers','insights'];
        $this->tab = in_array($tab, $valid, true) ? $tab : 'overview';
        if (auth()->check()) {
            // persist preferred tab for the user
            $state = app(\App\Services\AnalyticsFilterState::class);
            if (method_exists($state, 'set')) {
                $current = $state->get(auth()->id());
                $current['bi_tab'] = $this->tab;
                $state->set(auth()->id(), $current);
            }
        }
        $this->dispatch('$refresh');
    }

    protected function widgetsByTab(string $tab): array
    {
        return match ($tab) {
            'overview' => [
                // High-level KPIs and unified chart
                \App\Filament\Admin\Widgets\RevenueOverviewWidget::class,
                \App\Filament\Widgets\AdminChartsWidget::class,
                // Ops pulse
                \App\Filament\Widgets\InfrastructureHealthWidget::class,
            ],
            'revenue' => [
                \App\Filament\Admin\Widgets\RevenueOverviewWidget::class,
                \App\Filament\Admin\Widgets\RevenueChartWidget::class,
                \App\Filament\Admin\Widgets\RevenueByMethodWidget::class,
                \App\Filament\Admin\Widgets\OrderMetricsWidget::class,
                \App\Filament\Admin\Widgets\RevenueForecastWidget::class,
            ],
            'users' => [
                \App\Filament\Admin\Widgets\UserGrowthChartWidget::class,
                \App\Filament\Admin\Widgets\UserGrowthWidget::class,
                \App\Filament\Admin\Widgets\UserSegmentationWidget::class,
            ],
            'servers' => [
                \App\Filament\Admin\Widgets\LocationPopularityWidget::class,
                \App\Filament\Admin\Widgets\ProtocolUsageWidget::class,
                \App\Filament\Admin\Widgets\ServerPerformanceWidget::class,
            ],
            'insights' => [
                \App\Filament\Admin\Widgets\InsightsWidget::class,
                \App\Filament\Admin\Widgets\RecommendationsWidget::class,
                \App\Filament\Admin\Widgets\ChurnPredictionWidget::class,
                \App\Filament\Admin\Widgets\RevenueForecastWidget::class,
            ],
            default => [
                \App\Filament\Admin\Widgets\RevenueOverviewWidget::class,
                \App\Filament\Admin\Widgets\RevenueChartWidget::class,
            ],
        };
    }

    private function mapRangeToService(string $range): string
    {
        return match ($range) {
            '7d' => '7_days',
            '30d' => '30_days',
            '90d' => '90_days',
            '1y' => '365_days',
            default => '30_days',
        };
    }
}
