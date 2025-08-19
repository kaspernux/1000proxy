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
            \App\Filament\Widgets\AdminChartsWidget::class,
        ];
    }
}
