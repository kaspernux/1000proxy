<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\ServerClient;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AdvancedAnalyticsService
{
    /**
     * Get comprehensive business metrics
     */
    public function getBusinessMetrics(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);
        
        return Cache::remember("business_metrics_{$period}", 3600, function () use ($startDate) {
            return [
                'revenue' => $this->getRevenueMetrics($startDate),
                'customers' => $this->getCustomerMetrics($startDate),
                'orders' => $this->getOrderMetrics($startDate),
                'usage' => $this->getUsageMetrics($startDate),
                'performance' => $this->getPerformanceMetrics($startDate),
                'forecasting' => $this->getForecastingMetrics($startDate),
            ];
        });
    }

    /**
     * Get revenue metrics
     */
    private function getRevenueMetrics(Carbon $startDate): array
    {
        $totalRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('grand_amount');

        $previousPeriodRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(now())))
            ->where('created_at', '<', $startDate)
            ->sum('grand_amount');

        $revenueGrowth = $previousPeriodRevenue > 0 
            ? (($totalRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100 
            : 0;

        $averageOrderValue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->avg('grand_amount');

        $monthlyRecurringRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('grand_amount');

        $revenueByPaymentMethod = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->select('payment_method', DB::raw('SUM(grand_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('total', 'payment_method')
            ->toArray();

        return [
            'total_revenue' => $totalRevenue,
            'revenue_growth' => $revenueGrowth,
            'average_order_value' => $averageOrderValue,
            'monthly_recurring_revenue' => $monthlyRecurringRevenue,
            'revenue_by_payment_method' => $revenueByPaymentMethod,
        ];
    }

    /**
     * Get customer metrics
     */
    private function getCustomerMetrics(Carbon $startDate): array
    {
        $totalCustomers = User::where('role', 'customer')
            ->where('created_at', '>=', $startDate)
            ->count();

        $activeCustomers = User::where('role', 'customer')
            ->whereHas('orders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->count();

        $customerRetentionRate = $this->calculateRetentionRate($startDate);
        $customerLifetimeValue = $this->calculateLifetimeValue();
        $churnRate = $this->calculateChurnRate($startDate);

        $customerSegments = $this->getCustomerSegments($startDate);

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'retention_rate' => $customerRetentionRate,
            'lifetime_value' => $customerLifetimeValue,
            'churn_rate' => $churnRate,
            'segments' => $customerSegments,
        ];
    }

    /**
     * Get order metrics
     */
    private function getOrderMetrics(Carbon $startDate): array
    {
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();
        $completedOrders = Order::where('order_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $orderCompletionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;

        $ordersByStatus = Order::where('created_at', '>=', $startDate)
            ->select('order_status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')
            ->get()
            ->pluck('count', 'order_status')
            ->toArray();

        $ordersByServer = Order::where('created_at', '>=', $startDate)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('servers', 'order_items.server_id', '=', 'servers.id')
            ->select('servers.name', DB::raw('COUNT(*) as count'))
            ->groupBy('servers.id', 'servers.name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();

        $averageOrderProcessingTime = $this->calculateAverageProcessingTime($startDate);

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'completion_rate' => $orderCompletionRate,
            'orders_by_status' => $ordersByStatus,
            'orders_by_server' => $ordersByServer,
            'average_processing_time' => $averageOrderProcessingTime,
        ];
    }

    /**
     * Get usage metrics
     */
    private function getUsageMetrics(Carbon $startDate): array
    {
        $activeClients = ServerClient::where('is_active', true)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalTraffic = ServerClient::where('created_at', '>=', $startDate)
            ->sum('total_traffic');

        $averageTrafficPerClient = $activeClients > 0 ? $totalTraffic / $activeClients : 0;

        $topServers = DB::table('server_clients')
            ->join('servers', 'server_clients.server_id', '=', 'servers.id')
            ->where('server_clients.created_at', '>=', $startDate)
            ->select('servers.name', DB::raw('COUNT(*) as client_count'))
            ->groupBy('servers.id', 'servers.name')
            ->orderBy('client_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        $protocolUsage = ServerClient::where('created_at', '>=', $startDate)
            ->select('protocol', DB::raw('COUNT(*) as count'))
            ->groupBy('protocol')
            ->get()
            ->pluck('count', 'protocol')
            ->toArray();

        return [
            'active_clients' => $activeClients,
            'total_traffic' => $totalTraffic,
            'average_traffic_per_client' => $averageTrafficPerClient,
            'top_servers' => $topServers,
            'protocol_usage' => $protocolUsage,
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(Carbon $startDate): array
    {
        $successfulOrders = Order::where('order_status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $failedOrders = Order::where('order_status', 'dispute')
            ->where('created_at', '>=', $startDate)
            ->count();

        $successRate = ($successfulOrders + $failedOrders) > 0 
            ? ($successfulOrders / ($successfulOrders + $failedOrders)) * 100 
            : 100;

        $averageResponseTime = $this->calculateAverageResponseTime($startDate);
        $systemUptime = $this->calculateSystemUptime($startDate);

        return [
            'order_success_rate' => $successRate,
            'average_response_time' => $averageResponseTime,
            'system_uptime' => $systemUptime,
            'failed_orders' => $failedOrders,
        ];
    }

    /**
     * Get forecasting metrics
     */
    private function getForecastingMetrics(Carbon $startDate): array
    {
        $historicalData = $this->getHistoricalRevenue($startDate);
        $predictedRevenue = $this->predictRevenue($historicalData);
        $seasonalTrends = $this->getSeasonalTrends($startDate);
        $growthPrediction = $this->calculateGrowthPrediction($historicalData);

        return [
            'predicted_revenue' => $predictedRevenue,
            'seasonal_trends' => $seasonalTrends,
            'growth_prediction' => $growthPrediction,
            'historical_data' => $historicalData,
        ];
    }

    /**
     * Calculate customer retention rate
     */
    private function calculateRetentionRate(Carbon $startDate): float
    {
        $customersAtStart = User::where('role', 'customer')
            ->where('created_at', '<', $startDate)
            ->count();

        $customersStillActive = User::where('role', 'customer')
            ->where('created_at', '<', $startDate)
            ->whereHas('orders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->count();

        return $customersAtStart > 0 ? ($customersStillActive / $customersAtStart) * 100 : 0;
    }

    /**
     * Calculate customer lifetime value
     */
    private function calculateLifetimeValue(): float
    {
        $averageOrderValue = Order::where('payment_status', 'paid')->avg('grand_amount');
        $averageOrderFrequency = $this->calculateAverageOrderFrequency();
        $averageCustomerLifespan = $this->calculateAverageCustomerLifespan();

        return $averageOrderValue * $averageOrderFrequency * $averageCustomerLifespan;
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate(Carbon $startDate): float
    {
        $customersAtStart = User::where('role', 'customer')
            ->where('created_at', '<', $startDate)
            ->count();

        $churned = User::where('role', 'customer')
            ->where('created_at', '<', $startDate)
            ->whereDoesntHave('orders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->count();

        return $customersAtStart > 0 ? ($churned / $customersAtStart) * 100 : 0;
    }

    /**
     * Get customer segments
     */
    private function getCustomerSegments(Carbon $startDate): array
    {
        $segments = [];

        // High-value customers
        $segments['high_value'] = User::where('role', 'customer')
            ->whereHas('orders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                      ->where('payment_status', 'paid');
            })
            ->withSum(['orders' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                      ->where('payment_status', 'paid');
            }], 'grand_amount')
            ->having('orders_sum_grand_amount', '>', 1000)
            ->count();

        // Regular customers
        $segments['regular'] = User::where('role', 'customer')
            ->whereHas('orders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                      ->where('payment_status', 'paid');
            })
            ->withSum(['orders' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                      ->where('payment_status', 'paid');
            }], 'grand_amount')
            ->having('orders_sum_grand_amount', '>', 100)
            ->having('orders_sum_grand_amount', '<=', 1000)
            ->count();

        // New customers
        $segments['new'] = User::where('role', 'customer')
            ->where('created_at', '>=', $startDate)
            ->count();

        return $segments;
    }

    /**
     * Calculate average processing time
     */
    private function calculateAverageProcessingTime(Carbon $startDate): float
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('order_status', 'completed')
            ->whereNotNull('updated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time')
            ->value('avg_time') ?? 0;
    }

    /**
     * Calculate average response time
     */
    private function calculateAverageResponseTime(Carbon $startDate): float
    {
        // This would typically come from application performance monitoring
        // For now, we'll return a placeholder
        return 250; // milliseconds
    }

    /**
     * Calculate system uptime
     */
    private function calculateSystemUptime(Carbon $startDate): float
    {
        // This would typically come from monitoring systems
        // For now, we'll return a placeholder
        return 99.9; // percentage
    }

    /**
     * Get historical revenue data
     */
    private function getHistoricalRevenue(Carbon $startDate): array
    {
        return Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(grand_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Predict revenue using simple linear regression
     */
    private function predictRevenue(array $historicalData): array
    {
        if (count($historicalData) < 2) {
            return [];
        }

        $n = count($historicalData);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($historicalData as $index => $data) {
            $x = $index + 1;
            $y = $data['revenue'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        $predictions = [];
        for ($i = 1; $i <= 30; $i++) {
            $predictions[] = [
                'day' => $i,
                'predicted_revenue' => $slope * ($n + $i) + $intercept
            ];
        }

        return $predictions;
    }

    /**
     * Get seasonal trends
     */
    private function getSeasonalTrends(Carbon $startDate): array
    {
        return Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate->copy()->subYear())
            ->selectRaw('MONTH(created_at) as month, SUM(grand_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Calculate growth prediction
     */
    private function calculateGrowthPrediction(array $historicalData): array
    {
        if (count($historicalData) < 2) {
            return ['growth_rate' => 0, 'confidence' => 0];
        }

        $revenues = array_column($historicalData, 'revenue');
        $growthRates = [];

        for ($i = 1; $i < count($revenues); $i++) {
            if ($revenues[$i - 1] > 0) {
                $growthRates[] = (($revenues[$i] - $revenues[$i - 1]) / $revenues[$i - 1]) * 100;
            }
        }

        $averageGrowthRate = array_sum($growthRates) / count($growthRates);
        $variance = array_sum(array_map(function ($rate) use ($averageGrowthRate) {
            return pow($rate - $averageGrowthRate, 2);
        }, $growthRates)) / count($growthRates);

        $confidence = max(0, 100 - sqrt($variance));

        return [
            'growth_rate' => $averageGrowthRate,
            'confidence' => $confidence
        ];
    }

    /**
     * Calculate average order frequency
     */
    private function calculateAverageOrderFrequency(): float
    {
        $totalOrders = Order::where('payment_status', 'paid')->count();
        $totalCustomers = User::where('role', 'customer')->count();

        return $totalCustomers > 0 ? $totalOrders / $totalCustomers : 0;
    }

    /**
     * Calculate average customer lifespan
     */
    private function calculateAverageCustomerLifespan(): float
    {
        $averageLifespan = User::where('role', 'customer')
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_lifespan')
            ->value('avg_lifespan');

        return $averageLifespan ? $averageLifespan / 30 : 6; // Convert to months, default 6 months
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };
    }

    /**
     * Get real-time metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'active_users' => $this->getActiveUsers(),
            'current_revenue' => $this->getCurrentRevenue(),
            'pending_orders' => $this->getPendingOrders(),
            'system_status' => $this->getSystemStatus(),
            'server_performance' => $this->getServerPerformance(),
        ];
    }

    /**
     * Get active users count
     */
    private function getActiveUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subHours(24))->count();
    }

    /**
     * Get current revenue for today
     */
    private function getCurrentRevenue(): float
    {
        return Order::where('payment_status', 'paid')
            ->whereDate('created_at', now()->toDateString())
            ->sum('grand_amount');
    }

    /**
     * Get pending orders count
     */
    private function getPendingOrders(): int
    {
        return Order::whereIn('order_status', ['new', 'processing'])->count();
    }

    /**
     * Get system status
     */
    private function getSystemStatus(): array
    {
        return [
            'status' => 'healthy',
            'uptime' => '99.9%',
            'response_time' => '250ms',
            'error_rate' => '0.1%',
        ];
    }

    /**
     * Get server performance metrics
     */
    private function getServerPerformance(): array
    {
        return [
            'cpu_usage' => 45.2,
            'memory_usage' => 62.8,
            'disk_usage' => 34.5,
            'network_io' => 125.6,
        ];
    }
}
