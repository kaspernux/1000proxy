<?php

namespace App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages;

use App\Filament\Admin\Resources\BusinessIntelligenceResource;
use App\Services\BusinessIntelligenceService;
use Filament\Resources\Pages\Page;

class BusinessIntelligenceDashboard extends Page
{
    protected static string $resource = BusinessIntelligenceResource::class;

    protected string $view = 'filament.admin.business-intelligence.dashboard';

    protected static ?string $title = 'Business Intelligence Dashboard';

    public array $analytics = [];

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AdminDashboardStatsWidget::class,
            \App\Filament\Widgets\InfrastructureHealthWidget::class,
        ];
    }

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    protected function getViewData(): array
    {
        return [
            'analytics' => $this->analytics,
        ];
    }

    protected function loadAnalytics(): void
    {
        $biService = app(BusinessIntelligenceService::class);
        $this->analytics = $biService->getDashboardAnalytics('30_days');
    }
}
