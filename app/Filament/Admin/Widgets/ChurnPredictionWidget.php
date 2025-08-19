<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

class ChurnPredictionWidget extends ChartWidget
{
    protected ?string $heading = 'Churn Risk Distribution';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        // Mock churn prediction data
        return [
            'datasets' => [
                [
                    'label' => 'Users at Risk',
                    'data' => [150, 75, 25], // Low, Medium, High risk
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',  // Green for low risk
                        'rgba(245, 158, 11, 0.8)',  // Yellow for medium risk
                        'rgba(239, 68, 68, 0.8)',   // Red for high risk
                    ],
                ],
            ],
            'labels' => ['Low Risk', 'Medium Risk', 'High Risk'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
