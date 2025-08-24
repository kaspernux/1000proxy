<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Server;

class ServerMiniCharts extends LineChartWidget
{
    public ?Server $record = null;
    protected static bool $isLazy = true;
    protected ?string $pollingInterval = '120s';
    protected ?string $heading = 'Performance Trend';

    protected function getData(): array
    {
        // Expect performance_metrics to optionally include history arrays
        // e.g., ["response_history" => [120, 150, ...], "uptime_history" => [99.9, 99.7, ...]]
        $metrics = $this->record?->performance_metrics ?? [];
        $responseHistory = array_slice((array)($metrics['response_history'] ?? []), -12);
        $uptimeHistory = array_slice((array)($metrics['uptime_history'] ?? []), -12);

        // Fallback single point from current fields to avoid empty chart
        if (empty($responseHistory) && $this->record?->response_time_ms !== null) {
            $responseHistory = [ (int) $this->record->response_time_ms ];
        }
        if (empty($uptimeHistory) && $this->record?->uptime_percentage !== null) {
            $uptimeHistory = [ (float) $this->record->uptime_percentage ];
        }

        // Labels: last N intervals; keep it simple 1..N
        $count = max(count($responseHistory), count($uptimeHistory));
        $labels = range(1, max($count, 1));

        return [
            'datasets' => [
                [
                    'label' => 'Response (ms)',
                    'data' => $responseHistory,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.2)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Uptime %',
                    'data' => $uptimeHistory,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.2)',
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
            'options' => [
                'scales' => [
                    'y' => [ 'beginAtZero' => true ],
                    'y1' => [ 'beginAtZero' => true, 'position' => 'right', 'suggestedMax' => 100 ],
                ],
                'plugins' => [
                    'legend' => [ 'display' => true ],
                ],
            ],
        ];
    }
}
