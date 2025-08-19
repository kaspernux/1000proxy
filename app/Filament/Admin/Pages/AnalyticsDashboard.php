<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use App\Services\BusinessIntelligenceService;
use BackedEnum;

class AnalyticsDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Analytics Dashboard';
    protected string $view = 'filament.admin.pages.analytics-dashboard';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'analytics';

    public $timeRange = '30d';
    public $selectedMetric = 'revenue';
    public $analyticsData = [];
    public $paymentMethodFilter = '';
    public $planFilter = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->canViewReports();
    }

    public function mount(): void
    {
        if (auth()->check()) {
            $filterState = app(\App\Services\AnalyticsFilterState::class)->get(auth()->id());
            $this->timeRange = $filterState['time_range'] ?? $this->timeRange;
            $this->paymentMethodFilter = $filterState['payment_method'] ?? '';
            $this->planFilter = $filterState['plan'] ?? '';
        }
        $this->loadAnalyticsData();
    }

    public function updatedTimeRange(): void
    {
        if (auth()->check()) {
            app(\App\Services\AnalyticsFilterState::class)->setTimeRange(auth()->id(), $this->timeRange);
        }
        $this->refreshAnalytics();
    }

    public function updatedPaymentMethodFilter(): void
    {
        $this->persistFilters();
        $this->refreshAnalytics();
    }

    public function updatedPlanFilter(): void
    {
        $this->persistFilters();
        $this->refreshAnalytics();
    }

    protected function persistFilters(): void
    {
        if (auth()->check()) {
            app(\App\Services\AnalyticsFilterState::class)->setFilters(auth()->id(), [
                'payment_method' => $this->paymentMethodFilter,
                'plan' => $this->planFilter,
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshAnalytics'),

            \Filament\Actions\Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportReport'),

            \Filament\Actions\Action::make('schedule')
                ->label('Schedule Report')
                ->icon('heroicon-o-clock')
                ->action('scheduleReport'),
        ];
    }

    public function refreshAnalytics(): void
    {
        Cache::tags(['analytics'])->flush();
        $this->loadAnalyticsData();

        \Filament\Notifications\Notification::make()
            ->title('Analytics Refreshed')
            ->success()
            ->send();
    }

    public function exportReport(): void
    {
        // Implementation for exporting analytics report
        \Filament\Notifications\Notification::make()
            ->title('Report Export Started')
            ->body('Your analytics report will be generated and emailed to you shortly.')
            ->success()
            ->send();
    }

    public function scheduleReport(): void
    {
        // Implementation for scheduling regular reports
        \Filament\Notifications\Notification::make()
            ->title('Report Scheduled')
            ->body('Regular analytics reports have been scheduled.')
            ->success()
            ->send();
    }

    protected function loadAnalyticsData(): void
    {
        $serviceRange = app(\App\Services\AnalyticsFilterState::class)->mapToServiceRange($this->timeRange);
        $cacheKey = "analytics_dashboard_v2_{$serviceRange}";
        $bi = app(BusinessIntelligenceService::class);
        $raw = Cache::tags(['analytics'])->remember($cacheKey, now()->addMinutes(5), fn() => $bi->getDashboardAnalytics($serviceRange));

        $data = [
            'overview' => $this->mapOverview($raw['data'] ?? []),
            'revenue' => $raw['data']['revenue'] ?? [],
            'users' => $raw['data']['users'] ?? [],
            'servers' => $raw['data']['servers'] ?? [],
            // Normalize performance metrics to expected structure for blade
            'performance' => (function () use ($raw) {
                $perf = $raw['data']['performance'] ?? [];
                if (empty($perf) || !is_array($perf)) return [];

                $normalized = [];
                foreach ($perf as $key => $val) {
                    // If already in expected structure
                    if (is_array($val) && array_key_exists('value', $val)) {
                        $normalized[$key] = [
                            'value' => $val['value'],
                            'unit' => $val['unit'] ?? '',
                            'status' => $val['status'] ?? 'good',
                        ];
                        continue;
                    }

                    // Map known collections to a single representative metric
                    switch ($key) {
                        case 'response_times':
                            // Expect array like ['api'=>ms, 'database'=>ms, 'cache'=>ms]
                            $api = is_array($val) ? ($val['api'] ?? null) : null;
                            $value = is_numeric($api) ? (int)$api : (is_numeric($val) ? (int)$val : 0);
                            $normalized['response_time'] = [
                                'value' => $value,
                                'unit' => 'ms',
                                'status' => $value <= 300 ? 'good' : 'warning',
                            ];
                            break;
                        case 'error_rates':
                            // Sum 4xx/5xx/timeouts as a proxy percentage
                            $sum = 0;
                            if (is_array($val)) {
                                foreach ($val as $v) { $sum += is_numeric($v) ? (float)$v : 0; }
                            } elseif (is_numeric($val)) {
                                $sum = (float)$val;
                            }
                            $normalized['error_rate'] = [
                                'value' => round($sum, 2),
                                'unit' => '%',
                                'status' => $sum < 5 ? 'good' : 'warning',
                            ];
                            break;
                        case 'api_usage':
                            $req = is_array($val) ? ($val['total_requests'] ?? 0) : (is_numeric($val) ? (int)$val : 0);
                            $normalized['api_requests'] = [
                                'value' => (int)$req,
                                'unit' => '',
                                'status' => 'good',
                            ];
                            break;
                        case 'database_performance':
                            $qt = is_array($val) ? ($val['query_time'] ?? null) : null;
                            $value = is_numeric($qt) ? (int)$qt : (is_numeric($val) ? (int)$val : 0);
                            $normalized['db_query_time'] = [
                                'value' => $value,
                                'unit' => 'ms',
                                'status' => $value <= 300 ? 'good' : 'warning',
                            ];
                            break;
                        case 'cache_hit_rate':
                            $value = is_numeric($val) ? (float)$val : 0;
                            $normalized['cache_hit_rate'] = [
                                'value' => round($value, 1),
                                'unit' => '%',
                                'status' => $value >= 85 ? 'good' : 'warning',
                            ];
                            break;
                        case 'system_load':
                            $cpu = is_array($val) ? ($val['cpu'] ?? null) : null;
                            $value = is_numeric($cpu) ? (int)$cpu : (is_numeric($val) ? (int)$val : 0);
                            $normalized['system_cpu'] = [
                                'value' => $value,
                                'unit' => '%',
                                'status' => $value <= 80 ? 'good' : 'warning',
                            ];
                            break;
                        default:
                            // Fallback for unknown keys
                            $scalar = is_numeric($val) ? $val : (is_array($val) ? (reset($val) ?: 0) : 0);
                            $normalized[$key] = [
                                'value' => (float)$scalar,
                                'unit' => '',
                                'status' => 'good',
                            ];
                    }
                }

                return $normalized;
            })(),
            'forecasting' => $raw['data']['forecasts'] ?? [],
            // Transform raw segment arrays into simple counts for legacy blade
            'segmentation' => (function () use ($raw) {
                $segments = $raw['data']['segments']['segments'] ?? [];
                if (empty($segments)) return [];
                return [
                    'High Value' => count($segments['high_value'] ?? []),
                    'Frequent Buyers' => count($segments['frequent_buyers'] ?? []),
                    'At Risk' => count($segments['at_risk'] ?? []),
                    'New Customers' => count($segments['new_customers'] ?? []),
                    'Churned' => count($segments['churned_customers'] ?? []),
                ];
            })(),
            'churn' => [
                'churn_rate' => $raw['data']['revenue']['churn_rate'] ?? 0,
                'retention_rate' => isset($raw['data']['revenue']['churn_rate']) ? 100 - ($raw['data']['revenue']['churn_rate']) : 0,
                'at_risk_customers' => count($raw['data']['segments']['segments']['at_risk'] ?? []),
                'churned_customers' => count($raw['data']['segments']['segments']['churned_customers'] ?? []),
            ],
        ];

        // Normalize users block for blade expectations
        if (isset($data['users']) && is_array($data['users'])) {
            // Inject activity_rate if missing (active vs total users)
            if (!isset($data['users']['activity_rate'])) {
                $total = (int)($data['users']['total_users'] ?? 0);
                $active = (int)($data['users']['active_users'] ?? 0);
                $data['users']['activity_rate'] = $total > 0 ? round(($active / $total) * 100, 2) : 0;
            }

            // Normalize daily_registrations to have formatted_date and count fields
            if (isset($data['users']['daily_registrations']) && is_iterable($data['users']['daily_registrations'])) {
                $daily = [];
                foreach ($data['users']['daily_registrations'] as $date => $value) {
                    if (is_array($value) && isset($value['date'])) {
                        $daily[] = $value;
                    } else {
                        $count = is_array($value) ? ($value['registrations'] ?? $value['count'] ?? 0) : ($value->registrations ?? $value->count ?? 0);
                        $daily[] = [
                            'date' => is_string($date) ? $date : ($value['date'] ?? now()->toDateString()),
                            'formatted_date' => \Carbon\Carbon::parse(is_string($date) ? $date : ($value['date'] ?? now()))->format('M j'),
                            'count' => (int) $count,
                        ];
                    }
                }
                usort($daily, fn($a, $b) => strcmp($a['date'], $b['date']));
                $data['users']['daily_registrations'] = $daily;
            }
        }

        // Normalize revenue daily series for blade (expects array with formatted_date)
        if (isset($data['revenue']['daily_revenue']) && is_iterable($data['revenue']['daily_revenue'])) {
            $daily = [];
            foreach ($data['revenue']['daily_revenue'] as $date => $value) {
                if (is_array($value) && isset($value['date'])) { // already structured
                    $daily[] = $value;
                } else {
                    $daily[] = [
                        'date' => $date,
                        'formatted_date' => \Carbon\Carbon::parse($date)->format('M j'),
                        'revenue' => is_array($value) ? ($value['revenue'] ?? 0) : $value,
                    ];
                }
            }
            usort($daily, fn($a, $b) => strcmp($a['date'], $b['date']));
            $data['revenue']['daily_revenue'] = $daily;
            $data['revenue']['total_period'] = collect($daily)->sum('revenue');
            $data['revenue']['average_daily'] = count($daily) ? round($data['revenue']['total_period'] / count($daily), 2) : 0;
            $data['revenue']['peak_day'] = collect($daily)->sortByDesc('revenue')->first();
        }

        if (!empty($this->paymentMethodFilter) && isset($data['revenue']['revenue_by_method'])) {
            $pm = strtolower($this->paymentMethodFilter);
            $data['revenue']['revenue_by_method'] = collect($data['revenue']['revenue_by_method'])
                ->filter(fn($row, $key) => strtolower($key) === $pm || (is_array($row) && strtolower($row['payment_method'] ?? '') === $pm))
                ->all();
        }
        if (!empty($this->planFilter) && isset($data['revenue']['revenue_by_plan'])) {
            $plan = strtolower($this->planFilter);
            $data['revenue']['revenue_by_plan'] = collect($data['revenue']['revenue_by_plan'])
                ->filter(fn($row) => str_contains(strtolower($row->plan_name ?? $row['plan_name'] ?? ''), $plan))
                ->values()->all();
        }

        $this->analyticsData = $data;
    }

    protected function getTimeRangeFilter(): array
    {
        $end = now();

        return match ($this->timeRange) {
            '24h' => ['start' => $end->copy()->subDay(), 'end' => $end],
            '7d' => ['start' => $end->copy()->subWeek(), 'end' => $end],
            '30d' => ['start' => $end->copy()->subMonth(), 'end' => $end],
            '90d' => ['start' => $end->copy()->subMonths(3), 'end' => $end],
            '1y' => ['start' => $end->copy()->subYear(), 'end' => $end],
            default => ['start' => $end->copy()->subMonth(), 'end' => $end],
        };
    }

    protected function mapOverview(array $data): array
    {
        $revenue = $data['revenue']['total_revenue'] ?? 0;
        $growth = $data['revenue']['revenue_growth'] ?? 0;
        $orders = $data['revenue']['order_count'] ?? 0;
        $aov = $data['revenue']['average_order_value'] ?? 0;
        $usersTotal = $data['users']['total_users'] ?? 0;
        $activeUsers = $data['users']['active_users'] ?? 0;
        $conversion = $data['users']['conversion_rate'] ?? 0;
        $serversOnline = $data['servers']['health_metrics']['servers_online'] ?? 0;
        $avgUptime = $data['servers']['health_metrics']['average_uptime'] ?? 0;

        return [
            'total_revenue' => [
                'value' => $revenue,
                'formatted' => '$' . number_format($revenue, 2),
                'growth' => $growth,
                'trend' => ($growth ?? 0) >= 0 ? 'up' : 'down',
            ],
            'new_customers' => [
                'value' => $usersTotal,
                'growth' => $growth,
                'trend' => ($growth ?? 0) >= 0 ? 'up' : 'down',
            ],
            'total_orders' => [
                'value' => $orders,
                'growth' => $growth,
                'trend' => ($growth ?? 0) >= 0 ? 'up' : 'down',
            ],
            'active_servers' => [
                'value' => $serversOnline,
                'clients' => $activeUsers,
                'utilization' => $avgUptime,
            ],
            'avg_order_value' => [
                'value' => $aov,
                'formatted' => '$' . number_format($aov, 2),
            ],
            'conversion_rate' => [
                'value' => $conversion,
                'formatted' => number_format($conversion, 1) . '%',
            ],
        ];
    }

    // Legacy detailed query methods removed in favor of BusinessIntelligenceService delegation

    protected function getUserAnalytics(array $timeRange): array
    {
        // User registration trends
        $dailyRegistrations = [];
        $current = $timeRange['start']->copy();

        while ($current <= $timeRange['end']) {
            $count = Customer::where(DB::raw('DATE(created_at)'), $current->format('Y-m-d'))->count();
            $dailyRegistrations[] = [
                'date' => $current->format('Y-m-d'),
                'count' => $count,
                'formatted_date' => $current->format('M j'),
            ];
            $current->addDay();
        }

        // User activity patterns
        $activeUsers = Customer::whereHas('orders', function ($query) use ($timeRange) {
            $query->whereBetween('created_at', [$timeRange['start'], $timeRange['end']]);
        })->count();

        $totalUsers = Customer::count();
        $newUsers = Customer::whereBetween('created_at', [$timeRange['start'], $timeRange['end']])->count();

        // User segments
        $userSegments = [
            'new' => $newUsers,
            'active' => $activeUsers,
            'inactive' => $totalUsers - $activeUsers,
        ];

        return [
            'daily_registrations' => $dailyRegistrations,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'new_users' => $newUsers,
            'segments' => $userSegments,
            'activity_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
        ];
    }

    protected function getServerAnalytics(array $timeRange): array
    {
        // Server utilization by location
        $serversByLocation = DB::table('servers')
            ->select('location', DB::raw('COUNT(*) as count'), DB::raw('AVG(CASE WHEN status = "active" THEN 1 ELSE 0 END) * 100 as uptime'))
            ->groupBy('location')
            ->get()
            ->map(function ($item) {
                return [
                    'location' => $item->location,
                    'count' => $item->count,
                    'uptime' => round($item->uptime, 1),
                ];
            });

        // Most popular servers
        $popularServers = DB::table('server_clients')
            ->join('server_inbounds', 'server_clients.server_inbound_id', '=', 'server_inbounds.id')
            ->join('servers', 'server_inbounds.server_id', '=', 'servers.id')
            ->select('servers.name', 'servers.location', DB::raw('COUNT(*) as client_count'))
            ->groupBy('servers.id', 'servers.name', 'servers.location')
            ->orderByDesc('client_count')
            ->limit(10)
            ->get();

        // Server performance metrics
        $averageUptime = Server::avg('uptime_percentage') ?? 0;
        $totalCapacity = Server::sum('max_clients');
        $usedCapacity = ServerClient::where('status', 'active')->count();

        return [
            'by_location' => $serversByLocation,
            'popular_servers' => $popularServers,
            'average_uptime' => round($averageUptime, 1),
            'capacity_utilization' => $totalCapacity > 0 ? round(($usedCapacity / $totalCapacity) * 100, 1) : 0,
            'total_servers' => Server::count(),
            'active_servers' => Server::where('status', 'active')->count(),
        ];
    }

    protected function getPerformanceMetrics(array $timeRange): array
    {
        // System performance indicators
        $avgResponseTime = rand(80, 150); // Simulated - replace with actual metrics
        $uptime = 99.8; // Simulated - replace with actual uptime tracking
        $errorRate = rand(1, 5) / 100; // Simulated error rate

        // Order processing metrics
        $avgOrderProcessingTime = Order::whereBetween('created_at', [$timeRange['start'], $timeRange['end']])
            ->whereNotNull('completed_at')
            ->get()
            ->avg(function ($order) {
                return $order->created_at->diffInMinutes($order->completed_at);
            }) ?? 0;

        return [
            'response_time' => [
                'value' => $avgResponseTime,
                'unit' => 'ms',
                'status' => $avgResponseTime < 200 ? 'good' : 'warning',
            ],
            'uptime' => [
                'value' => $uptime,
                'unit' => '%',
                'status' => $uptime > 99.5 ? 'good' : 'warning',
            ],
            'error_rate' => [
                'value' => $errorRate,
                'unit' => '%',
                'status' => $errorRate < 0.05 ? 'good' : 'warning',
            ],
            'order_processing_time' => [
                'value' => round($avgOrderProcessingTime, 1),
                'unit' => 'minutes',
                'status' => $avgOrderProcessingTime < 5 ? 'good' : 'warning',
            ],
        ];
    }

    protected function getCustomerBehaviorAnalytics(array $timeRange): array
    {
        // Purchase patterns
        $purchasePatterns = DB::table('orders')
            ->whereBetween('created_at', [$timeRange['start'], $timeRange['end']])
            ->where('payment_status', 'paid')
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with 0
        for ($i = 0; $i < 24; $i++) {
            if (!isset($purchasePatterns[$i])) {
                $purchasePatterns[$i] = 0;
            }
        }
        ksort($purchasePatterns);

        // Customer lifetime value calculation
        $avgCustomerLifetimeValue = Customer::whereHas('orders', function ($query) {
            $query->where('payment_status', 'paid');
        })->withSum('orders', 'grand_amount')->avg('orders_sum_grand_amount') ?? 0;

        // Repeat purchase rate
        $customersWithMultiplePurchases = Customer::whereHas('orders', function ($query) {
            $query->where('payment_status', 'paid');
        }, '>', 1)->count();

        $totalCustomersWithPurchases = Customer::whereHas('orders', function ($query) {
            $query->where('payment_status', 'paid');
        })->count();

        $repeatPurchaseRate = $totalCustomersWithPurchases > 0
            ? ($customersWithMultiplePurchases / $totalCustomersWithPurchases) * 100
            : 0;

        return [
            'purchase_patterns' => $purchasePatterns,
            'avg_lifetime_value' => round($avgCustomerLifetimeValue, 2),
            'repeat_purchase_rate' => round($repeatPurchaseRate, 1),
            'peak_hour' => array_search(max($purchasePatterns), $purchasePatterns),
        ];
    }

    protected function getRevenueForecast(array $timeRange): array
    {
        // Simple linear regression forecast based on historical data
        $historicalRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [
                $timeRange['start']->copy()->subDays(30),
                $timeRange['end']
            ])
            ->selectRaw('DATE(created_at) as date, SUM(grand_amount) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        if ($historicalRevenue->count() < 7) {
            return ['forecast' => [], 'confidence' => 'low'];
        }

        // Calculate trend
        $revenues = $historicalRevenue->pluck('revenue')->toArray();
        $n = count($revenues);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($revenues);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($i + 1) * $revenues[$i];
            $sumX2 += ($i + 1) * ($i + 1);
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Generate forecast for next 7 days
        $forecast = [];
        for ($i = 1; $i <= 7; $i++) {
            $forecastValue = $intercept + $slope * ($n + $i);
            $forecast[] = [
                'date' => $timeRange['end']->copy()->addDays($i)->format('Y-m-d'),
                'predicted_revenue' => max(0, round($forecastValue, 2)),
                'formatted_date' => $timeRange['end']->copy()->addDays($i)->format('M j'),
            ];
        }

        return [
            'forecast' => $forecast,
            'trend' => $slope > 0 ? 'increasing' : 'decreasing',
            'confidence' => $n > 14 ? 'high' : ($n > 7 ? 'medium' : 'low'),
        ];
    }

    protected function getCustomerSegmentation(array $timeRange): array
    {
        // RFM Analysis (Recency, Frequency, Monetary)
        $customers = Customer::with('orders')->get();
        $segments = [];

        foreach ($customers as $customer) {
            $orders = $customer->orders->where('payment_status', 'paid');

            if ($orders->isEmpty()) continue;

            $recency = $orders->max('created_at')->diffInDays(now());
            $frequency = $orders->count();
            $monetary = $orders->sum('grand_amount');

            // Simple segmentation logic
            if ($monetary > 500 && $frequency > 5 && $recency < 30) {
                $segment = 'VIP';
            } elseif ($monetary > 200 && $frequency > 2 && $recency < 60) {
                $segment = 'High Value';
            } elseif ($frequency > 1 && $recency < 90) {
                $segment = 'Regular';
            } elseif ($recency < 30) {
                $segment = 'New';
            } else {
                $segment = 'At Risk';
            }

            if (!isset($segments[$segment])) {
                $segments[$segment] = 0;
            }
            $segments[$segment]++;
        }

        return $segments;
    }

    protected function getChurnAnalysis(array $timeRange): array
    {
        // Calculate churn rate (customers who haven't made a purchase in the last 90 days)
        $churnThreshold = now()->subDays(90);

        $totalCustomers = Customer::whereHas('orders')->count();
        $churnedCustomers = Customer::whereHas('orders', function ($query) use ($churnThreshold) {
            $query->where('created_at', '<', $churnThreshold);
        })->whereDoesntHave('orders', function ($query) use ($churnThreshold) {
            $query->where('created_at', '>=', $churnThreshold);
        })->count();

        $churnRate = $totalCustomers > 0 ? ($churnedCustomers / $totalCustomers) * 100 : 0;

        // Identify at-risk customers (no purchase in last 30 days)
        $atRiskThreshold = now()->subDays(30);
        $atRiskCustomers = Customer::whereHas('orders', function ($query) use ($atRiskThreshold) {
            $query->where('created_at', '<', $atRiskThreshold);
        })->whereDoesntHave('orders', function ($query) use ($atRiskThreshold) {
            $query->where('created_at', '>=', $atRiskThreshold);
        })->count();

        return [
            'churn_rate' => round($churnRate, 1),
            'churned_customers' => $churnedCustomers,
            'at_risk_customers' => $atRiskCustomers,
            'retention_rate' => round(100 - $churnRate, 1),
        ];
    }

    protected function getGeographicAnalytics(array $timeRange): array
    {
        // Revenue by server location
        $revenueByLocation = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('server_plans', 'order_items.server_plan_id', '=', 'server_plans.id')
            ->join('servers', 'server_plans.server_id', '=', 'servers.id')
            ->whereBetween('orders.created_at', [$timeRange['start'], $timeRange['end']])
            ->where('orders.payment_status', 'paid')
            ->select('servers.location', DB::raw('SUM(orders.grand_amount) as revenue'), DB::raw('COUNT(orders.id) as order_count'))
            ->groupBy('servers.location')
            ->orderByDesc('revenue')
            ->get();

        return [
            'revenue_by_location' => $revenueByLocation,
            'top_location' => $revenueByLocation->first(),
            'total_locations' => $revenueByLocation->count(),
        ];
    }

    protected function calculateConversionRate(array $timeRange): float
    {
        // Simple conversion rate calculation
        $visitors = rand(1000, 5000); // Replace with actual visitor tracking
        $orders = Order::whereBetween('created_at', [$timeRange['start'], $timeRange['end']])
            ->where('payment_status', 'paid')
            ->count();

        return $visitors > 0 ? ($orders / $visitors) * 100 : 0;
    }

    public function updateTimeRange(string $range): void
    {
        $this->timeRange = $range;
        $this->loadAnalyticsData();
    }

    public function getAnalyticsData(): array
    {
        return $this->analyticsData;
    }
}
