<?php

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Filament\Admin\Resources\BusinessIntelligenceResource;
use Filament\Resources\Pages\Page;

class InsightsReport extends Page
{
    protected static string $resource = BusinessIntelligenceResource::class;

    protected string $view = 'filament.admin.business-intelligence.insights';

    protected static ?string $title = 'AI Insights & Recommendations';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\InsightsWidget::class,
            \App\Filament\Admin\Widgets\RecommendationsWidget::class,
            \App\Filament\Admin\Widgets\RevenueForecastWidget::class,
        ];
    }
}
