<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerClient;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminChartsWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue & Growth Analytics';
    protected static ?string $description = 'Unified revenue, orders, customer and client growth trends with multi-range filters.';

    public function getHeading(): string
    {
        // Ensure deterministic heading (used by dataset persistence & added as data attribute downstream)
        return static::$heading ?? 'Revenue & Growth Analytics';
    }

    protected static ?int $sort = 3; // after stats(1) + infra(2)

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '400px';
    protected static bool $isLazy = false; // ensure immediate render so skeleton replaced quickly

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today (hourly)',
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
            '90days' => 'Last 90 Days',
            'year' => 'This Year',
        ];
    }

    /**
     * Livewire listeners for real-time chart refresh (orders trigger revenue/orders update, serverStatus for client growth potential).
     */
    protected $listeners = [
        'orderPaid' => '$refresh',
        'refreshRevenueMetrics' => '$refresh',
    ];

    protected function getData(): array
    {
        $key = 'dash.chart.filter.' . ($this->filter ?? 'week');
        return Cache::remember($key, 300, function () {
            return match($this->filter) {
                'today' => $this->buildTodayData(),
                'week' => $this->buildDailyWindowData(7),
                'month' => $this->buildDailyWindowData(30),
                '90days' => $this->buildDailyWindowData(90),
                'year' => $this->buildYearData(),
                default => $this->buildDailyWindowData(7),
            };
        });
    }

    /** Hourly data for current day */
    protected function buildTodayData(): array
    {
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        // Batch aggregate queries (group by hour)
        $ordersAgg = Order::selectRaw('HOUR(created_at) as h, COUNT(*) as cnt, SUM(grand_amount) as revenue')
            ->where('payment_status','paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('h')->pluck('cnt','h');
        $revenueAgg = Order::selectRaw('HOUR(created_at) as h, SUM(grand_amount) as revenue')
            ->where('payment_status','paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('h')->pluck('revenue','h');
        $customerAgg = Customer::selectRaw('HOUR(created_at) as h, COUNT(*) c')
            ->whereBetween('created_at', [$start,$end])
            ->groupBy('h')->pluck('c','h');
        $clientAgg = ServerClient::selectRaw('HOUR(created_at) as h, COUNT(*) c')
            ->whereBetween('created_at', [$start,$end])
            ->groupBy('h')->pluck('c','h');

        $labels=[];$revenue=[];$orders=[];$customers=[];$clients=[];
        for ($h=0;$h<24;$h++) {
            $labels[] = str_pad($h,2,'0',STR_PAD_LEFT).':00';
            $revenue[] = round((float)($revenueAgg[$h] ?? 0),2);
            $orders[] = (int)($ordersAgg[$h] ?? 0);
            $customers[] = (int)($customerAgg[$h] ?? 0);
            $clients[] = (int)($clientAgg[$h] ?? 0);
        }
        return $this->formatDatasets($labels,$revenue,$orders,$customers,$clients,true);
    }

    /** Generic daily window (last N days) */
    protected function buildDailyWindowData(int $days): array
    {
        $start = Carbon::now()->subDays($days-1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $ordersAgg = Order::selectRaw('DATE(created_at) d, COUNT(*) cnt, SUM(grand_amount) revenue')
            ->where('payment_status','paid')
            ->whereBetween('created_at', [$start,$end])
            ->groupBy('d')->get()->keyBy('d');
        $customerAgg = Customer::selectRaw('DATE(created_at) d, COUNT(*) c')
            ->whereBetween('created_at', [$start,$end])
            ->groupBy('d')->pluck('c','d');
        $clientAgg = ServerClient::selectRaw('DATE(created_at) d, COUNT(*) c')
            ->whereBetween('created_at', [$start,$end])
            ->groupBy('d')->pluck('c','d');

        $labels=[];$revenue=[];$orders=[];$customers=[];$clients=[];
        for ($d = $days-1; $d >= 0; $d--) {
            $date = Carbon::now()->subDays($d)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M j');
            $row = $ordersAgg[$date] ?? null;
            $revenue[] = round((float)($row->revenue ?? 0),2);
            $orders[] = (int)($row->cnt ?? 0);
            $customers[] = (int)($customerAgg[$date] ?? 0);
            $clients[] = (int)($clientAgg[$date] ?? 0);
        }
        return $this->formatDatasets($labels,$revenue,$orders,$customers,$clients,true);
    }

    /** Monthly data for current year */
    protected function buildYearData(): array
    {
        $year = Carbon::now()->year;
        $ordersAgg = Order::selectRaw('MONTH(created_at) m, COUNT(*) cnt, SUM(grand_amount) revenue')
            ->where('payment_status','paid')
            ->whereYear('created_at',$year)
            ->groupBy('m')->get()->keyBy('m');
        $customerAgg = Customer::selectRaw('MONTH(created_at) m, COUNT(*) c')
            ->whereYear('created_at',$year)
            ->groupBy('m')->pluck('c','m');
        $clientAgg = ServerClient::selectRaw('MONTH(created_at) m, COUNT(*) c')
            ->whereYear('created_at',$year)
            ->groupBy('m')->pluck('c','m');

        $labels=[];$revenue=[];$orders=[];$customers=[];$clients=[];
        for ($m=1;$m<=12;$m++) {
            $date = Carbon::createFromDate($year,$m,1);
            if ($date->gt(Carbon::now())) break;
            $labels[] = $date->format('M Y');
            $row = $ordersAgg[$m] ?? null;
            $revenue[] = round((float)($row->revenue ?? 0),2);
            $orders[] = (int)($row->cnt ?? 0);
            $customers[] = (int)($customerAgg[$m] ?? 0);
            $clients[] = (int)($clientAgg[$m] ?? 0);
        }
        return $this->formatDatasets($labels,$revenue,$orders,$customers,$clients,true);
    }

    protected function formatDatasets(array $labels,array $revenue,array $orders,array $customers,array $clients,bool $fillRevenue=false): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenue,
                    'borderColor' => 'rgb(59,130,246)',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
                    'fill' => $fillRevenue,
                    'tension' => 0.35,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Orders',
                    'data' => $orders,
                    'borderColor' => 'rgb(34,197,94)',
                    'backgroundColor' => 'rgba(34,197,94,0.1)',
                    'fill' => false,
                    'tension' => 0.35,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'New Customers',
                    'data' => $customers,
                    'borderColor' => 'rgb(168,85,247)',
                    'backgroundColor' => 'rgba(168,85,247,0.1)',
                    'fill' => false,
                    'tension' => 0.35,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'New Clients',
                    'data' => $clients,
                    'borderColor' => 'rgb(249,115,22)',
                    'backgroundColor' => 'rgba(249,115,22,0.1)',
                    'fill' => false,
                    'tension' => 0.35,
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

    public function extraAttributes(): array
    {
        // Provide the heading as a data attribute so the persistence plugin can key reliably.
        return [ 'data-chart-heading' => $this->getHeading() ];
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
