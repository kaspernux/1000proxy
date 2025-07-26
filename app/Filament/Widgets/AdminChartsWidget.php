<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerClient;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminChartsWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue & Growth Analytics';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '400px';

    public ?string $filter = '30days';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        // Determine date range based on filter
        switch ($filter) {
            case '7days':
                $startDate = Carbon::now()->subDays(7);
                $dateFormat = 'M j';
                $groupBy = 'DATE(created_at)';
                break;
            case '90days':
                $startDate = Carbon::now()->subDays(90);
                $dateFormat = 'M j';
                $groupBy = 'DATE(created_at)';
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $dateFormat = 'M Y';
                $groupBy = 'YEAR(created_at), MONTH(created_at)';
                break;
            default: // 30days
                $startDate = Carbon::now()->subDays(30);
                $dateFormat = 'M j';
                $groupBy = 'DATE(created_at)';
                break;
        }

        // Get revenue data
        $revenueData = Order::where('payment_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw($groupBy . ' as period, SUM(grand_amount) as revenue, COUNT(*) as orders')
            ->groupBy(DB::raw($groupBy))
            ->orderBy('period')
            ->get();

        // Get customer registration data
        $customerData = Customer::where('created_at', '>=', $startDate)
            ->selectRaw($groupBy . ' as period, COUNT(*) as customers')
            ->groupBy(DB::raw($groupBy))
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Get server client data
        $clientData = ServerClient::where('created_at', '>=', $startDate)
            ->selectRaw($groupBy . ' as period, COUNT(*) as clients')
            ->groupBy(DB::raw($groupBy))
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Prepare labels and data arrays
        $labels = [];
        $revenues = [];
        $orders = [];
        $customers = [];
        $clients = [];

        // Fill data arrays
        foreach ($revenueData as $item) {
            $date = $filter === 'year'
                ? Carbon::createFromDate(null, $item->period, 1)->format($dateFormat)
                : Carbon::parse($item->period)->format($dateFormat);

            $labels[] = $date;
            $revenues[] = round($item->revenue, 2);
            $orders[] = $item->orders;
            $customers[] = $customerData->get($item->period)?->customers ?? 0;
            $clients[] = $clientData->get($item->period)?->clients ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenues,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Orders',
                    'data' => $orders,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'New Customers',
                    'data' => $customers,
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'New Clients',
                    'data' => $clients,
                    'borderColor' => 'rgb(249, 115, 22)',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.dataset.label === "Revenue ($)") {
                                label += "$" + context.parsed.y.toFixed(2);
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Date',
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue ($)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toFixed(2); }',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Count',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}

class AdminServerStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Server Status & Performance';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get server status distribution
        $serverStatusData = Server::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Get server utilization data
        $servers = Server::where('status', 'up')
            ->select('name', 'total_clients', 'max_clients', 'country')
            ->get();

        $utilizationData = $servers->map(function ($server) {
            $utilization = $server->max_clients > 0
                ? ($server->total_clients / $server->max_clients) * 100
                : 0;
            return [
                'name' => $server->name,
                'utilization' => round($utilization, 1),
                'country' => $server->country,
                'clients' => $server->total_clients,
                'max_clients' => $server->max_clients,
            ];
        })->sortByDesc('utilization')->take(10);

        return [
            'datasets' => [
                [
                    'label' => 'Server Utilization (%)',
                    'data' => $utilizationData->pluck('utilization')->toArray(),
                    'backgroundColor' => $utilizationData->map(function ($item) {
                        if ($item['utilization'] > 90) return 'rgba(239, 68, 68, 0.8)';
                        if ($item['utilization'] > 70) return 'rgba(245, 158, 11, 0.8)';
                        return 'rgba(34, 197, 94, 0.8)';
                    })->toArray(),
                    'borderColor' => 'rgba(75, 85, 99, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $utilizationData->map(function ($item) {
                return $item['name'] . ' (' . $item['country'] . ')';
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": " + context.parsed.x + "%";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Utilization (%)',
                    ],
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Servers',
                    ],
                ],
            ],
        ];
    }
}