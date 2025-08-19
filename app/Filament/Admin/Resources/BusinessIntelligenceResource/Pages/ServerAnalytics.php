<?php

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Filament\Admin\Resources\BusinessIntelligenceResource;
use Filament\Resources\Pages\Page;

class ServerAnalytics extends Page
{
    protected static string $resource = BusinessIntelligenceResource::class;

    protected string $view = 'filament.admin.business-intelligence.servers';

    protected static ?string $title = 'Server Analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\LocationPopularityWidget::class,
            \App\Filament\Admin\Widgets\ProtocolUsageWidget::class,
            \App\Filament\Admin\Widgets\TrendsWidget::class,
        ];
    }
}
