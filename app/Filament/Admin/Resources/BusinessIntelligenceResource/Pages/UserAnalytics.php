<?php

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Filament\Admin\Resources\BusinessIntelligenceResource;
use Filament\Resources\Pages\Page;

class UserAnalytics extends Page
{
    protected static string $resource = BusinessIntelligenceResource::class;

    protected string $view = 'filament.admin.business-intelligence.users';

    protected static ?string $title = 'User Analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\UserGrowthChartWidget::class,
            \App\Filament\Admin\Widgets\UserSegmentationWidget::class,
            \App\Filament\Widgets\AdminDashboardStatsWidget::class,
        ];
    }
}
