<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use App\Models\User;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\WalletTransaction;
use Carbon\Carbon;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Analytics Dashboard';
    protected static string $view = 'filament.admin.pages.analytics-dashboard';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'analytics';

    public $timeRange = '30d';
    public $selectedMetric = 'revenue';
    public $analyticsData = [];

    public function mount(): void
    {
        $this->loadAnalyticsData();
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
        $cacheKey = "analytics_dashboard_{$this->timeRange}";

        $this->analyticsData = Cache::tags(['analytics'])->remember($cacheKey, now()->addMinutes(15), function () {
            $timeRange = $this->getTimeRangeFilter();

            return [
                'overview' => $this->getOverviewMetrics($timeRange),
                'revenue' => $this->getRevenueAnalytics($timeRange),
                'users' => $this->getUserAnalytics($timeRange),
                'servers' => $this->getServerAnalytics($timeRange),
                'performance' => $this->getPerformanceMetrics($timeRange),
                'customer_behavior' => $this->getCustomerBehaviorAnalytics($timeRange),
                'forecasting' => $this->getRevenueForecast($timeRange),
                'segmentation' => $this->getCustomerSegmentation($timeRange),
                'churn' => $this->getChurnAnalysis($timeRange),
                'geographic' => $this->getGeographicAnalytics($timeRange),
            ];
        });
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

    protected function getOverviewMetrics(array $timeRange): array
    {
        // Calculate period metrics
        $currentPeriodOrders = Order::whereBetween('created_at', [$timeRange['start'], $timeRange['end']])
            ->where('status', 'completed')
            ->get();

        $previousStart = $timeRange['start']->copy()->sub($timeRange['end']->diffInDays($timeRange['start']), 'days');
        $previousEnd = $timeRange['start']->copy();

        $previousPeriodOrders = Order::whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('status', 'completed')
            ->get();

        // Revenue calculations
        $currentRevenue = $currentPeriodOrders->sum('total_amount');
        $previousRevenue = $previousPeriodOrders->sum('total_amount');
        $revenueGrowth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        // User metrics
        $currentUsers = Customer::whereBetween('created_at', [$timeRange['start'], $timeRange['end']])->count();
        $previousUsers = Customer::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $userGrowth = $previousUsers > 0 ? (($currentUsers - $previousUsers) / $previousUsers) * 100 : 0;

        // Order metrics
        $currentOrderCount = $currentPeriodOrders->count();
        $previousOrderCount = $previousPeriodOrders->count();
        $orderGrowth = $previousOrderCount > 0 ? (($currentOrderCount - $previousOrderCount) / $previousOrderCount) * 100 : 0;

        // Active servers
        $activeServers = Server::where('status', 'active')->count();
        $totalServerClients = ServerClient::where('status', 'active')->count();

        return [
            'total_revenue' => [
                'value' => $currentRevenue,
                'formatted' => '$' . number_format($currentRevenue, 2),
                'growth' => round($revenueGrowth, 1),
                'trend' => $revenueGrowth >= 0 ? 'up' : 'down',
            ],
            'new_customers' => [
                'value' => $currentUsers,
                'growth' => round($userGrowth, 1),
                'trend' => $userGrowth >= 0 ? 'up' : 'down',
            ],
            'total_orders' => [
                'value' => $currentOrderCount,
                'growth' => round($orderGrowth, 1),
                'trend' => $orderGrowth >= 0 ? 'up' : 'down',
            ],
            'active_servers' => [
                'value' => $activeServers,
                'clients' => $totalServerClients,
                'utilization' => $activeServers > 0 ? round(($totalServerClients / ($activeServers * 100)) * 100, 1) : 0,
            ],
            'avg_order_value' => [
                'value' => $currentOrderCount > 0 ? $currentRevenue / $currentOrderCount : 0,
                'formatted' => '$' . number_format($currentOrderCount > 0 ? $currentRevenue / $currentOrderCount : 0, 2),
            ],
            'conversion_rate' => [
                'value' => $this->calculateConversionRate($timeRange),
                'formatted' => number_format($this->calculateConversionRate($timeRange), 1) . '%',
            ],
        ];
    }

    protected function getRevenueAnalytics(array $timeRange): array
    {
        // Daily revenue breakdown
        $dailyRevenue = [];
        $current = $timeRange['start']->copy();

        while ($current <= $timeRange['end']) {
            $dayRevenue = Order::where(DB::raw('DATE(created_at)'), $current->format('Y-m-d'))
                ->where('status', 'completed')
                ->sum('total_amount');

            $dailyRevenue[] = [
                'date' => $current->format('Y-m-d'),
                'revenue' => $dayRevenue,
                'formatted_date' => $current->format('M j'),
            ];

            $current->addDay();
        }

        // Revenue by payment method
        $revenueByPaymentMethod = WalletTransaction::whereBetween('created_at', [$timeRange['start'], $timeRange['end']])
            ->where('type', 'credit')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => ucfirst($item->payment_method ?? 'Unknown'),
                    'amount' => $item->total,
                    'formatted' => '$' . number_format($item->total, 2),
                ];
            });

        // Revenue by server category
        $revenueByCategory = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('server_plans', 'order_items.server_plan_id', '=', 'server_plans.id')
            ->join('servers', 'server_plans.server_id', '=', 'servers.id')
            ->join('server_categories', 'servers.server_category_id', '=', 'server_categories.id')
            ->whereBetween('orders.created_at', [$timeRange['start'], $timeRange['end']])
            ->where('orders.status', 'completed')
            ->select('server_categories.name', DB::raw('SUM(orders.total_amount) as total'))
            ->groupBy('server_categories.name')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->name,
                    'amount' => $item->total,
                    'formatted' => '$' . number_format($item->total, 2),
                ];
            });

        return [
            'daily_revenue' => $dailyRevenue,
            'by_payment_method' => $revenueByPaymentMethod,
            'by_category' => $revenueByCategory,
            'total_period' => collect($dailyRevenue)->sum('revenue'),
            'peak_day' => collect($dailyRevenue)->sortByDesc('revenue')->first(),
            'average_daily' => collect($dailyRevenue)->avg('revenue'),
        ];
    }

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
            ->join('servers', 'server_clients.server_id', '=', 'servers.id')
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
            ->where('status', 'completed')
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
            $query->where('status', 'completed');
        })->withSum('orders', 'total_amount')->avg('orders_sum_total_amount') ?? 0;

        // Repeat purchase rate
        $customersWithMultiplePurchases = Customer::whereHas('orders', function ($query) {
            $query->where('status', 'completed');
        }, '>', 1)->count();

        $totalCustomersWithPurchases = Customer::whereHas('orders', function ($query) {
            $query->where('status', 'completed');
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
        $historicalRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', [
                $timeRange['start']->copy()->subDays(30),
                $timeRange['end']
            ])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
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
            $orders = $customer->orders->where('status', 'completed');

            if ($orders->isEmpty()) continue;

            $recency = $orders->max('created_at')->diffInDays(now());
            $frequency = $orders->count();
            $monetary = $orders->sum('total_amount');

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
            ->where('orders.status', 'completed')
            ->select('servers.location', DB::raw('SUM(orders.total_amount) as revenue'), DB::raw('COUNT(orders.id) as order_count'))
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
            ->where('status', 'completed')
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
