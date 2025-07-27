<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RevenueAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Analytics';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 2;

    protected static ?string $pollingInterval = '60s';

    public ?string $filter = 'today';

    protected function getData(): array
    {
        $filter = $this->filter;

        return Cache::remember("revenue_analytics_{$filter}", 300, function () use ($filter) {
            return match ($filter) {
                'today' => $this->getTodayData(),
                'week' => $this->getWeekData(),
                'month' => $this->getMonthData(),
                'year' => $this->getYearData(),
                default => $this->getTodayData(),
            };
        });
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'year' => 'This Year',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toFixed(2); }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    private function getTodayData(): array
    {
        $labels = [];
        $revenueData = [];
        $ordersData = [];

        // Get hourly data for today
        for ($hour = 0; $hour < 24; $hour++) {
            $startTime = now()->startOfDay()->addHours($hour);
            $endTime = $startTime->copy()->addHour();

            $labels[] = $startTime->format('H:00');

            // Calculate revenue for this hour (sum grand_amount of paid orders)
            $revenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->sum('grand_amount');
            $revenueData[] = round($revenue, 2);

            // Count paid orders for this hour
            $orders = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->count();
            $ordersData[] = $orders;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Paid Orders)',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Paid Orders',
                    'data' => $ordersData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getWeekData(): array
    {
        $labels = [];
        $revenueData = [];
        $ordersData = [];

        // Get daily data for this week
        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $labels[] = $date->format('M j');

            // Calculate revenue for this day (sum grand_amount of paid orders)
            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('grand_amount');
            $revenueData[] = round($revenue, 2);

            // Count paid orders for this day
            $orders = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->count();
            $ordersData[] = $orders;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Paid Orders)',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Paid Orders',
                    'data' => $ordersData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getMonthData(): array
    {
        $labels = [];
        $revenueData = [];
        $ordersData = [];

        // Get weekly data for this month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $currentDate = $startOfMonth->copy();

        while ($currentDate <= $endOfMonth) {
            $weekEnd = $currentDate->copy()->addDays(6);
            if ($weekEnd > $endOfMonth) {
                $weekEnd = $endOfMonth;
            }

            $labels[] = $currentDate->format('M j') . ' - ' . $weekEnd->format('j');

            // Calculate revenue for this week (sum grand_amount of paid orders)
            $revenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$currentDate, $weekEnd])
                ->sum('grand_amount');
            $revenueData[] = round($revenue, 2);

            // Count paid orders for this week
            $orders = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$currentDate, $weekEnd])
                ->count();
            $ordersData[] = $orders;

            $currentDate = $weekEnd->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Paid Orders)',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Paid Orders',
                    'data' => $ordersData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getYearData(): array
    {
        $labels = [];
        $revenueData = [];
        $ordersData = [];

        // Get monthly data for this year
        for ($month = 11; $month >= 0; $month--) {
            $date = now()->subMonths($month);
            $labels[] = $date->format('M Y');

            // Calculate revenue for this month
            $revenue = WalletTransaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');

            $revenueData[] = round($revenue, 2);

            // Count orders for this month
            $orders = Order::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $ordersData[] = $orders;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Orders',
                    'data' => $ordersData,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}