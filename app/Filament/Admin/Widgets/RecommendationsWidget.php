<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class RecommendationsWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.recommendations-widget';
    protected static ?int $sort = 11;
    protected int|string|array $columnSpan = 'full';

    public array $recommendations = [];

    public function mount(): void
    {
        $bi = app(BusinessIntelligenceService::class);
        $data = Cache::remember('bi_recommendations', 300, fn () => $bi->generateInsights(['range' => '30_days']));
        $this->recommendations = $data['insights']['opportunity_insights'] ?? [];
    }

    protected function getViewData(): array
    {
        return [
            'recommendations' => $this->recommendations,
        ];
    }
}
