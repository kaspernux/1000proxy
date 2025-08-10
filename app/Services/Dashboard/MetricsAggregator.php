<?php

namespace App\Services\Dashboard;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsAggregator
{
    private int $shortTtl;
    private int $seriesTtl;

    public function __construct(int $shortTtl = 60, int $seriesTtl = 300)
    {
        $this->shortTtl = $shortTtl;
        $this->seriesTtl = $seriesTtl;
    }

    public function revenueSummary(): array
    {
        return Cache::remember('dash.revenue.summary', $this->shortTtl, function () {
            $total = Order::where('payment_status', 'paid')->sum('grand_amount') ?? 0;
            $monthTotal = Order::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('grand_amount') ?? 0;
            $lastMonth = Order::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->sum('grand_amount') ?? 0;
            $growth = $lastMonth == 0 ? ($monthTotal > 0 ? 100 : 0) : round((($monthTotal - $lastMonth) / $lastMonth) * 100, 1);
            $pending = Order::where('payment_status', 'pending')->sum('grand_amount') ?? 0;
            return compact('total', 'monthTotal', 'growth', 'pending');
        });
    }

    public function revenueDailySeries(int $days = 7): array
    {
        return Cache::remember("dash.revenue.daily.$days", $this->seriesTtl, function () use ($days) {
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $data[] = Order::where('payment_status', 'paid')
                    ->whereDate('created_at', $date)
                    ->sum('grand_amount');
            }
            return $data;
        });
    }

    public function revenueMonthSeries(): array
    {
        return Cache::remember('dash.revenue.monthSeries', $this->seriesTtl, function () {
            $data = [];
            $days = now()->daysInMonth;
            for ($i = 1; $i <= $days; $i++) {
                $date = now()->startOfMonth()->addDays($i - 1)->toDateString();
                $data[] = Order::where('payment_status', 'paid')
                    ->whereDate('created_at', $date)
                    ->sum('grand_amount');
            }
            return $data;
        });
    }

    public function customerSummary(): array
    {
        return Cache::remember('dash.customer.summary', $this->shortTtl, function () {
            $total = Customer::count();
            $newThisMonth = Customer::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
            $currentMonth = $newThisMonth;
            $lastMonth = Customer::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
            $subscriptionGrowth = $lastMonth == 0 ? ($currentMonth > 0 ? 100 : 0) : round((($currentMonth - $lastMonth)/$lastMonth)*100,1);
            $retained = $total > 0 ? round((Customer::where('last_login_at','>=', now()->subDays(90))->count() / $total) * 100, 1) : 0;
            return [
                'total' => $total,
                'newThisMonth' => $newThisMonth,
                'subscriptionGrowth' => $subscriptionGrowth,
                'retention' => $retained,
            ];
        });
    }

    public function customerDailySeries(int $days = 7): array
    {
        return Cache::remember("dash.customer.daily.$days", $this->seriesTtl, function () use ($days) {
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $data[] = Customer::whereDate('created_at', $date)->count();
            }
            return $data;
        });
    }

    public function serverSummary(): array
    {
        return Cache::remember('dash.server.summary', $this->shortTtl, function () {
            $total = Server::count();
            $online = Server::where('status', 'up')->count();
            $clients = ServerClient::count();
            $serversUp = Server::where('status','up')->get();
            $utilTotal = $serversUp->sum('total_clients');
            $utilMax = $serversUp->sum('max_clients_per_inbound');
            $utilization = $utilMax == 0 ? 0 : round(($utilTotal / $utilMax) * 100, 1);
            $statusDistribution = Server::select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count','status')->toArray();
            return compact('total','online','clients','utilization','statusDistribution');
        });
    }

    public function clientDailySeries(int $days = 7): array
    {
        return Cache::remember("dash.client.daily.$days", $this->seriesTtl, function () use ($days) {
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $data[] = ServerClient::whereDate('created_at', $date)->count();
            }
            return $data;
        });
    }

    public function systemHealth(): float
    {
        return Cache::remember('dash.system.health', $this->shortTtl, function () {
            $total = Server::count();
            if ($total === 0) return 0.0;
            $online = Server::where('status','up')->count();
            return round(($online / $total) * 100, 1);
        });
    }

    /**
     * Aggregate upstream / downstream traffic across all servers & clients.
     * Normalizes potential mixed storage of per-client (up/down) and per-server global_traffic_stats JSON.
     */
    public function serverTraffic(): array
    {
        return Cache::remember('dash.server.traffic', $this->shortTtl, function () {
            $totalUp = 0; $totalDown = 0;

            // Sum per-client traffic quickly using raw aggregates if columns exist
            try {
                $clientAgg = ServerClient::selectRaw('COALESCE(SUM(`up`),0) as up_sum, COALESCE(SUM(`down`),0) as down_sum')->first();
                if ($clientAgg) {
                    $totalUp += (int) ($clientAgg->up_sum ?? 0);
                    $totalDown += (int) ($clientAgg->down_sum ?? 0);
                }
            } catch (\Exception $e) {
                // Fallback to model iteration if schema mismatch
                ServerClient::chunk(1000, function ($chunk) use (&$totalUp, &$totalDown) {
                    foreach ($chunk as $c) {
                        $totalUp += $c->up ?? 0; $totalDown += $c->down ?? 0;
                    }
                });
            }

            // Include per-server global aggregate if provided
            Server::select(['id','global_traffic_stats'])->chunk(500, function ($servers) use (&$totalUp, &$totalDown) {
                foreach ($servers as $server) {
                    $stats = $server->global_traffic_stats;
                    if (is_array($stats)) {
                        $totalUp += $stats['up'] ?? 0; $totalDown += $stats['down'] ?? 0;
                    } elseif (is_string($stats)) {
                        $decoded = json_decode($stats, true);
                        if (is_array($decoded)) {
                            $totalUp += $decoded['up'] ?? 0; $totalDown += $decoded['down'] ?? 0;
                        }
                    }
                }
            });

            $total = $totalUp + $totalDown;
            return [
                'up' => $totalUp,
                'down' => $totalDown,
                'total' => $total,
                'displayUp' => $this->formatBytes($totalUp),
                'displayDown' => $this->formatBytes($totalDown),
                'displayTotal' => $this->formatBytes($total),
            ];
        });
    }

    /**
     * Hourly active connections trend (last 12 hours) + delta vs previous hour.
     */
    public function activeConnectionsTrend(int $hours = 12): array
    {
        $hours = max(2, min(48, $hours));
        return Cache::remember("dash.connections.trend.$hours", $this->shortTtl, function () use ($hours) {
            // Build per-hour buckets
            $chart = [];
            for ($i = $hours - 1; $i >= 0; $i--) {
                $start = now()->subHours($i);
                $end = $start->copy()->addHour();
                $count = ServerClient::where('status','active')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count();
                $chart[] = $count;
            }

            $current = end($chart) ?: 0; // last element
            $previous = $chart[count($chart)-2] ?? 0;
            $change = $current - $previous;
            $changePercent = $previous > 0 ? round(($change / $previous) * 100, 1) : 0.0;

            if ($change > 0) {
                $direction = 'up'; $icon = 'heroicon-m-arrow-trending-up'; $color = 'success';
                $description = "↗ $change (+$changePercent%) from last hour";
            } elseif ($change < 0) {
                $direction = 'down'; $icon = 'heroicon-m-arrow-trending-down'; $color = 'danger';
                $description = "↘ " . abs($change) . " ($changePercent%) from last hour";
            } else {
                $direction = 'flat'; $icon = 'heroicon-m-minus'; $color = 'gray';
                $description = '→ No change from last hour';
            }

            return [
                'current' => $current,
                'previous' => $previous,
                'change' => $change,
                'changePercent' => $changePercent,
                'direction' => $direction,
                'chart' => $chart,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
            ];
        });
    }

    // Helpers
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B','KB','MB','GB','TB','PB'];
        $i = (int) floor(log($bytes, 1024));
        $value = $bytes / pow(1024, $i);
        return round($value, $precision) . ' ' . $units[$i];
    }
}
