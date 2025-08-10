<?php

namespace App\Filament\Admin\Resources;

use App\Services\BusinessIntelligenceService;
use Filament\Resources\Resource;
use Filament\Pages\Page;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class BusinessIntelligenceResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Business Intelligence';

    protected static ?string $slug = 'business-intelligence';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Analytics';

    public static function getPages(): array
    {
        return [
            'index' => Pages\BusinessIntelligenceDashboard::route('/'),
            'revenue' => Pages\RevenueAnalytics::route('/revenue'),
            'users' => Pages\UserAnalytics::route('/users'),
            'servers' => Pages\ServerAnalytics::route('/servers'),
            'insights' => Pages\InsightsReport::route('/insights'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'analyst']) ?? false;
    }
}

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Services\BusinessIntelligenceService;
use Filament\Resources\Pages\Page;
use Filament\Pages\Dashboard;
use Filament\Widgets\Widget;

class BusinessIntelligenceDashboard extends Page
{
    protected static string $resource = \App\Filament\Admin\Resources\BusinessIntelligenceResource::class;

    protected static string $view = 'filament.admin.business-intelligence.dashboard';

    protected static ?string $title = 'Business Intelligence Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            // Unified KPI + growth/revenue stats
            \App\Filament\Widgets\AdminDashboardStatsWidget::class,
            \App\Filament\Admin\Widgets\UserGrowthWidget::class,
            \App\Filament\Admin\Widgets\OrderMetricsWidget::class,
            // Infrastructure health (replaces ServerPerformanceWidget)
            \App\Filament\Widgets\InfrastructureHealthWidget::class,
        ];
    }

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    protected function loadAnalytics(): void
    {
        $biService = app(BusinessIntelligenceService::class);
        $this->analytics = $biService->getDashboardAnalytics('30_days');
    }

    public $analytics = [];

    protected function getViewData(): array
    {
        return [
            'analytics' => $this->analytics,
        ];
    }
}

class RevenueAnalytics extends Page
{
    protected static string $resource = \App\Filament\Admin\Resources\BusinessIntelligenceResource::class;

    protected static string $view = 'filament.admin.business-intelligence.revenue';

    protected static ?string $title = 'Revenue Analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            // Consolidated multi-series charts widget replaces RevenueChartWidget
            \App\Filament\Widgets\AdminChartsWidget::class,
            \App\Filament\Admin\Widgets\RevenueByMethodWidget::class,
            \App\Filament\Admin\Widgets\RevenueForecastWidget::class,
        ];
    }
}

class UserAnalytics extends Page
{
    protected static string $resource = \App\Filament\Admin\Resources\BusinessIntelligenceResource::class;

    protected static string $view = 'filament.admin.business-intelligence.users';

    protected static ?string $title = 'User Analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\UserGrowthChartWidget::class,
            \App\Filament\Admin\Widgets\UserSegmentationWidget::class,
            \App\Filament\Admin\Widgets\ChurnPredictionWidget::class,
        ];
    }
}

class ServerAnalytics extends Page
{
    protected static string $resource = \App\Filament\Admin\Resources\BusinessIntelligenceResource::class;

    protected static string $view = 'filament.admin.business-intelligence.servers';

    protected static ?string $title = 'Server Analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            // Removed deprecated ServerUsageWidget (duplicated by LocationPopularity/ProtocolUsage)
            \App\Filament\Admin\Widgets\LocationPopularityWidget::class,
            \App\Filament\Admin\Widgets\ProtocolUsageWidget::class,
            \App\Filament\Admin\Widgets\TrendsWidget::class,
        ];
    }
}

class InsightsReport extends Page
{
    protected static string $resource = \App\Filament\Admin\Resources\BusinessIntelligenceResource::class;

    protected static string $view = 'filament.admin.business-intelligence.insights';

    protected static ?string $title = 'AI Insights & Recommendations';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\InsightsWidget::class,
            \App\Filament\Admin\Widgets\RecommendationsWidget::class,
            \App\Filament\Admin\Widgets\TrendsWidget::class,
        ];
    }
}
