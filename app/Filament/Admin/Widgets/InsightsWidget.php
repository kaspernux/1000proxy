<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

class InsightsWidget extends ChartWidget
{
    protected static ?string $heading = 'AI-Generated Insights';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Mock insights data
        return [
            'datasets' => [
                [
                    'label' => 'Insight Score',
                    'data' => [85, 92, 78, 88, 95, 82, 89],
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => ['Revenue', 'Users', 'Performance', 'Servers', 'Growth', 'Retention', 'Overall'],
        ];
    }

    protected function getType(): string
    {
        return 'radar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'r' => [
                    'beginAtZero' => true,
                    'max' => 100,
                ],
            ],
        ];
    }
}
