<?php

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Filament\Admin\Resources\BusinessIntelligenceResource;
use Filament\Resources\Pages\Page;

class RevenueAnalytics extends Page
{
    protected static string $resource = BusinessIntelligenceResource::class;

    protected string $view = 'filament.admin.business-intelligence.revenue';

    protected static ?string $title = 'Revenue Analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            // Overview KPIs and breakdowns
            \App\Filament\Admin\Widgets\RevenueOverviewWidget::class,
            // Trend chart
            \App\Filament\Admin\Widgets\RevenueChartWidget::class,
        ];
    }
}
