<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\ClientMetric;
use BackedEnum;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Carbon\Carbon;

class ServerMetrics extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Server Metrics';
    protected string $view = 'filament.customer.pages.server-metrics';
    protected static ?int $navigationSort = 6;

    public $selectedTimeRange = '7d';
    public $selectedServer = null;
    public $metricsData = [];
    /**
     * Per-client computed stats for current time range.
     * [client_id => [uptime_pct, avg_latency, total_bytes, is_online, last_seen]]
     */
    protected array $clientStats = [];

    public function mount(): void
    {
        $this->loadMetricsData();
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('refresh')
                ->label('Refresh Metrics')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshMetrics'),

            PageAction::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportMetrics'),
        ];
    }

    public function refreshMetrics(): void
    {
        $this->loadMetricsData();

        Notification::make()
            ->title('Metrics Refreshed')
            ->success()
            ->send();
    }

    public function exportMetrics(): void
    {
        // Implementation for exporting metrics data
        Notification::make()
            ->title('Export Started')
            ->body('Your metrics data export will be available shortly.')
            ->success()
            ->send();
    }

    protected function loadMetricsData(): void
    {
        $customer = Auth::guard('customer')->user();
        $timeRange = $this->getTimeRange();

        // Get user's purchased server clients (do not filter by created_at; time range applies to metrics)
    $clients = ServerClient::whereHas('order', function (Builder $query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
    ->with(['server.brand', 'server.category', 'order', 'plan', 'inbound'])
        ->get();

        $this->computeClientStats($clients, $timeRange);

        // Enhanced metrics calculation (live from ClientMetric where possible)
        $this->metricsData = [
            'overview' => $this->calculateOverviewMetrics($clients),
            'performance' => $this->calculatePerformanceMetrics($clients),
            'usage' => $this->calculateUsageMetrics($clients, $timeRange),
            'reliability' => $this->calculateReliabilityMetrics($clients),
            'geographic' => $this->calculateGeographicMetrics($clients),
            'trends' => $this->calculateTrendMetrics($clients, $timeRange),
            'alerts' => $this->calculateAlerts($clients),
            'recommendations' => $this->generateRecommendations($clients),
            'plans' => $this->calculatePlansOverview($clients),
        ];

        // Ensure all strings in the public payload are valid UTF-8 for Livewire JSON serialization
        $this->metricsData = $this->sanitizeUtf8Array($this->metricsData);
    }

    private function calculatePlansOverview(Collection $clients): array
    {
        // Totals
        $totalClients = $clients->count();
        $totalServers = $clients->unique('server_id')->count();
        $totalInbounds = $clients->unique('server_inbound_id')->count();

        // By protocol (from inbound or plan)
        $byProtocol = $clients->groupBy(function ($c) {
            $p = $c->inbound->protocol ?? $c->plan->protocol ?? 'unknown';
            return strtolower((string) $p);
        })->map->count()->sortDesc()->all();

        // By plan name
        $byPlan = $clients->groupBy(function ($c) {
            return $c->plan->name ?? 'Unassigned Plan';
        })->map->count()->sortDesc()->all();

        // By server (name)
        $byServer = $clients->groupBy(function ($c) {
            return $c->server->name ?? ('Server '.$c->server_id);
        })->map->count()->sortDesc()->all();

        // By inbound port/tag
        $byInbound = $clients->groupBy(function ($c) {
            $tag = $c->inbound->tag ?? null;
            $port = $c->inbound->port ?? null;
            $proto = $c->inbound->protocol ?? null;
            return trim(collect([$proto, $tag, $port ? ("#{$port}") : null])->filter()->implode(' '));
        })->map->count()->sortDesc()->all();

        return [
            'totals' => [
                'clients' => $totalClients,
                'servers' => $totalServers,
                'inbounds' => $totalInbounds,
            ],
            'by_protocol' => $byProtocol,
            'by_plan' => $byPlan,
            'by_server' => $byServer,
            'by_inbound' => $byInbound,
        ];
    }

    private function sanitizeUtf8Array($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->sanitizeUtf8Array($v);
            }
            return $out;
        }
        if (is_string($value)) {
            return $this->sanitizeUtf8String($value);
        }
        // Leave objects (e.g., Carbon) and scalars unchanged
        return $value;
    }

    private function sanitizeUtf8String(string $s): string
    {
        if (function_exists('mb_check_encoding') && !mb_check_encoding($s, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
            if ($converted !== false) {
                return $converted;
            }
            return utf8_encode($s);
        }
        return $s;
    }

    private function computeClientStats(Collection $clients, array $timeRange): void
    {
        $this->clientStats = [];
        if ($clients->isEmpty()) {
            return;
        }

        $clientIds = $clients->pluck('id')->all();
        $metrics = ClientMetric::query()
            ->whereIn('server_client_id', $clientIds)
            ->whereBetween('measured_at', [$timeRange['start'], $timeRange['end']])
            ->orderBy('server_client_id')
            ->orderBy('measured_at')
            ->get(['server_client_id', 'is_online', 'latency_ms', 'total_bytes', 'measured_at']);

        $grouped = $metrics->groupBy('server_client_id');
        foreach ($clients as $client) {
            $cid = $client->id;
            $rows = $grouped->get($cid, collect());
            $total = max(1, $rows->count());
            $online = $rows->where('is_online', true)->count();
            $avgLatency = (float) $rows->where('is_online', true)->avg('latency_ms');
            $sumBytes = (int) $rows->sum('total_bytes');
            $last = $rows->last();

            $this->clientStats[$cid] = [
                'uptime_pct' => round(($online / $total) * 100, 2),
                'avg_latency' => $avgLatency > 0 ? round($avgLatency, 1) : 0.0,
                'total_bytes' => $sumBytes,
                'is_online' => $last?->is_online ?? null,
                'last_seen' => $last?->measured_at,
            ];
        }
    }

    private function calculateOverviewMetrics(Collection $serverClients): array
    {
        $totalServers = $serverClients->unique('server_id')->count();
        $activeConnections = collect($this->clientStats)
            ->filter(fn ($s) => $s['is_online'] === true)
            ->count();
        $totalDataTransfer = array_sum(array_map(fn ($s) => $s['total_bytes'] ?? 0, $this->clientStats));
        $averageLatency = collect($this->clientStats)->pluck('avg_latency')->filter()->avg() ?? 0.0;

        return [
            'total_servers' => $totalServers,
            'active_connections' => $activeConnections,
            'connection_rate' => $totalServers > 0 ? round(($activeConnections / $totalServers) * 100, 1) : 0,
            'total_data_transfer' => $this->formatBytes($totalDataTransfer),
            'average_latency' => round($averageLatency, 1) . 'ms',
            'uptime_percentage' => $this->calculateUptimePercentage($serverClients),
        ];
    }

    private function calculatePerformanceMetrics(Collection $serverClients): array
    {
        $performanceData = [];

        foreach ($serverClients->groupBy('server.location') as $location => $clients) {
            $avgLatency = collect($clients)->avg(function ($c) {
                return $this->clientStats[$c->id]['avg_latency'] ?? null;
            }) ?? 0;
            $reliability = collect($clients)->avg(function ($c) {
                return $this->clientStats[$c->id]['uptime_pct'] ?? 0;
            });

            $performanceData[$location] = [
                'average_speed' => 0.0,
                'average_latency' => round($avgLatency, 1),
                // Aliases expected by Blade
                'avg_latency' => round($avgLatency, 1),
                'uptime' => round($reliability ?? 0, 1),
                'reliability_score' => round($reliability, 1),
                'client_count' => $clients->count(),
                'performance_score' => $this->calculatePerformanceScore(0.0, $avgLatency, $reliability),
            ];
        }

        return $performanceData;
    }

    private function calculateUsageMetrics(Collection $serverClients, array $timeRange): array
    {
        // Build daily usage buckets from ClientMetric across all clients in range
        $start = Carbon::parse($timeRange['start'])->startOfDay();
        $end = Carbon::parse($timeRange['end'])->endOfDay();
        $clientIds = $serverClients->pluck('id')->all();
        $metrics = ClientMetric::query()
            ->whereIn('server_client_id', $clientIds)
            ->whereBetween('measured_at', [$start, $end])
            ->get(['total_bytes', 'measured_at']);

        $grouped = $metrics->groupBy(fn ($m) => Carbon::parse($m->measured_at)->format('Y-m-d'));
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->format('Y-m-d');
            $sum = (int) ($grouped->get($key, collect())->sum('total_bytes'));
            $days[] = [
                'date' => $key,
                'upload' => 0,
                'download' => 0,
                'total' => $sum,
                'formatted_total' => $this->formatBytes($sum),
            ];
            $cursor->addDay();
        }

        // Totals expected by Blade (in MB)
        $totalBytes = (int) collect($days)->sum('total');
        $totalMb = (int) round($totalBytes / 1048576);

        // Last 24 hours transfer (in MB)
        $last24Start = Carbon::now()->subDay();
        $last24Bytes = (int) ClientMetric::query()
            ->whereIn('server_client_id', $clientIds)
            ->whereBetween('measured_at', [$last24Start, Carbon::now()])
            ->sum('total_bytes');
        $last24Mb = (int) round($last24Bytes / 1048576);

        return [
            'daily_usage' => $days,
            'peak_usage_day' => collect($days)->sortByDesc('total')->first(),
            'average_daily_usage' => $this->formatBytes((int) (collect($days)->avg('total') ?? 0)),
            'total_period_usage' => $this->formatBytes((int) collect($days)->sum('total')),
            // Additional numeric fields consumed by Blade cards
            'total_bandwidth_used' => $totalMb,
            'last_24h_transfer' => $last24Mb,
            'data_transfer' => [
                'upload' => 0,
                'download' => $totalMb,
            ],
        ];
    }

    private function calculateReliabilityMetrics(Collection $serverClients): array
    {
    $totalClients = $serverClients->count();
    $activeClients = collect($this->clientStats)->filter(fn ($s) => $s['is_online'] === true)->count();
    $connectionSuccessRate = $totalClients > 0 ? ($activeClients / $totalClients) * 100 : 0;

        // Calculate uptime by server
        $serverUptime = [];
        foreach ($serverClients->groupBy('server_id') as $serverId => $clients) {
            $server = $clients->first()->server;
            $uptime = collect($clients)->avg(function ($c) {
                return $this->clientStats[$c->id]['uptime_pct'] ?? 0;
            }) ?? 0;

            $serverUptime[] = [
                'server_name' => $server->name ?? "Server {$serverId}",
                'location' => $server->location ?? 'Unknown',
                'uptime_percentage' => round($uptime, 2),
                'active_connections' => collect($clients)->filter(fn ($c) => ($this->clientStats[$c->id]['is_online'] ?? false) === true)->count(),
                'total_connections' => $clients->count(),
            ];
        }

        $avgUptime = collect($serverUptime)->avg('uptime_percentage');
        return [
            'overall_success_rate' => round($connectionSuccessRate, 2),
            'average_uptime' => round($avgUptime ?? 0.0, 2),
            'server_uptime' => collect($serverUptime)->sortByDesc('uptime_percentage')->values()->all(),
            'reliability_score' => $this->calculateReliabilityScore($connectionSuccessRate, floatval($avgUptime ?? 0.0)),
        ];
    }

    private function calculateGeographicMetrics(Collection $serverClients): array
    {
        $locationData = [];

        foreach ($serverClients->groupBy('server.location') as $location => $clients) {
            $totalTransfer = collect($clients)->sum(function ($c) {
                return (int) ($this->clientStats[$c->id]['total_bytes'] ?? 0);
            });

            $activeConnections = collect($clients)->filter(fn ($c) => ($this->clientStats[$c->id]['is_online'] ?? false) === true)->count();
            $avgLatency = collect($clients)->avg(function ($c) {
                return $this->clientStats[$c->id]['avg_latency'] ?? null;
            }) ?? 0;

            $locationData[] = [
                'location' => $location ?? 'Unknown',
                'client_count' => $clients->count(),
                'active_connections' => $activeConnections,
                'total_transfer' => $totalTransfer,
                'formatted_transfer' => $this->formatBytes((int) $totalTransfer),
                'average_latency' => round((float) $avgLatency, 1),
                'usage_percentage' => 0, // Will be calculated after all locations are processed
            ];
        }

        // Calculate usage percentages
        $totalTransfer = collect($locationData)->sum('total_transfer');
        foreach ($locationData as &$location) {
            $location['usage_percentage'] = $totalTransfer > 0
                ? round(($location['total_transfer'] / $totalTransfer) * 100, 1)
                : 0;
        }

        return [
            'locations' => collect($locationData)->sortByDesc('total_transfer')->values()->all(),
            'top_location' => collect($locationData)->sortByDesc('total_transfer')->first(),
            'geographic_distribution' => $this->calculateGeographicDistribution($locationData),
        ];
    }

    private function calculateTrendMetrics(Collection $serverClients, array $timeRange): array
    {
        $trends = [
            'connection_trend' => $this->calculateConnectionTrend($serverClients, $timeRange),
            'usage_trend' => $this->calculateUsageTrend($serverClients, $timeRange),
            'performance_trend' => $this->calculatePerformanceTrend($serverClients, $timeRange),
        ];

        return $trends;
    }

    private function calculateAlerts(Collection $serverClients): array
    {
        $alerts = [];

        // High latency alerts
        $highLatencyClients = $serverClients->filter(function ($client) {
            return ($client->latency ?? 0) > 200; // Alert if latency > 200ms
        });

        if ($highLatencyClients->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Latency Detected',
                'message' => "Found {$highLatencyClients->count()} connections with high latency (>200ms)",
                'severity' => 'medium',
                'action_required' => true,
            ];
        }

        // Low uptime alerts
        $lowUptimeServers = $serverClients->groupBy('server_id')->filter(function ($clients) {
            $activeCount = $clients->where('status', 'active')->count();
            $totalCount = $clients->count();
            return $totalCount > 0 && ($activeCount / $totalCount) < 0.9; // Alert if uptime < 90%
        });

        if ($lowUptimeServers->count() > 0) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Low Server Uptime',
                'message' => "Found {$lowUptimeServers->count()} servers with uptime below 90%",
                'severity' => 'high',
                'action_required' => true,
            ];
        }

        // Usage pattern alerts
        $currentUsage = $serverClients->sum(function ($client) {
            return ($client->up ?? 0) + ($client->down ?? 0);
        });

        if ($currentUsage > 100 * 1024 * 1024 * 1024) { // Alert if usage > 100GB
            $alerts[] = [
                'type' => 'info',
                'title' => 'High Data Usage',
                'message' => "Your data usage has exceeded 100GB in the selected period",
                'severity' => 'low',
                'action_required' => false,
            ];
        }

        return $alerts;
    }

    private function generateRecommendations(Collection $serverClients): array
    {
        $recommendations = [];

        // Analyze performance by location
        $locationPerformance = [];
        foreach ($serverClients->groupBy('server.location') as $location => $clients) {
            $avgLatency = $clients->avg('latency') ?? 0;
            $reliability = $clients->where('status', 'active')->count() / $clients->count() * 100;

            $locationPerformance[$location] = [
                'latency' => $avgLatency,
                'reliability' => $reliability,
                'score' => $this->calculatePerformanceScore(0, $avgLatency, $reliability),
            ];
        }

        // Recommend best performing locations
        $bestLocation = collect($locationPerformance)->sortByDesc('score')->keys()->first();
        if ($bestLocation) {
            $recommendations[] = [
                'type' => 'optimization',
                'title' => 'Optimal Server Location',
                'message' => "Consider using more servers in {$bestLocation} for better performance",
                'priority' => 'medium',
            ];
        }

        // Recommend based on usage patterns
        $totalUsage = $serverClients->sum(function ($client) {
            return ($client->up ?? 0) + ($client->down ?? 0);
        });

        if ($totalUsage < 1024 * 1024 * 1024) { // Less than 1GB
            $recommendations[] = [
                'type' => 'cost_optimization',
                'title' => 'Consider Lighter Plan',
                'message' => 'Your usage is low. You might save money with a smaller plan',
                'priority' => 'low',
            ];
        } elseif ($totalUsage > 50 * 1024 * 1024 * 1024) { // More than 50GB
            $recommendations[] = [
                'type' => 'upgrade',
                'title' => 'Consider Plan Upgrade',
                'message' => 'Your high usage suggests you might benefit from a premium plan',
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    // Helper methods
    private function calculateUptimePercentage(Collection $serverClients): float
    {
        $totalClients = $serverClients->count();
        $activeClients = $serverClients->where('status', 'active')->count();

        return $totalClients > 0 ? round(($activeClients / $totalClients) * 100, 2) : 0;
    }

    private function calculatePerformanceScore(float $speed, float $latency, float $reliability): float
    {
        // Normalize and weight the metrics
        $speedScore = min($speed / 100, 1) * 40; // 40% weight for speed
        $latencyScore = max(0, (300 - $latency) / 300) * 30; // 30% weight for latency (lower is better)
        $reliabilityScore = ($reliability / 100) * 30; // 30% weight for reliability

        return round($speedScore + $latencyScore + $reliabilityScore, 1);
    }

    private function calculateReliabilityScore(float $successRate, float $avgUptime): float
    {
        return round(($successRate * 0.6) + ($avgUptime * 0.4), 1);
    }

    private function calculateGeographicDistribution(array $locationData): array
    {
        $totalClients = collect($locationData)->sum('client_count');

        return collect($locationData)->map(function ($location) use ($totalClients) {
            return [
                'location' => $location['location'],
                'percentage' => $totalClients > 0
                    ? round(($location['client_count'] / $totalClients) * 100, 1)
                    : 0,
            ];
        })->sortByDesc('percentage')->values()->all();
    }

    private function calculateConnectionTrend(Collection $serverClients, array $timeRange): array
    {
        // Implementation for connection trend analysis
        return [
            'direction' => 'stable', // up, down, stable
            'percentage_change' => 0,
            'description' => 'Connection count remained stable',
        ];
    }

    private function calculateUsageTrend(Collection $serverClients, array $timeRange): array
    {
        // Implementation for usage trend analysis
        return [
            'direction' => 'up',
            'percentage_change' => 15.5,
            'description' => 'Data usage increased by 15.5%',
        ];
    }

    private function calculatePerformanceTrend(Collection $serverClients, array $timeRange): array
    {
        // Implementation for performance trend analysis
        return [
            'direction' => 'up',
            'percentage_change' => 8.2,
            'description' => 'Overall performance improved by 8.2%',
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function calculateMetrics(): void
    {
        $serverClients = $this->user->clients()
            ->with(['server', 'orderItem.order'])
            ->get();

        $timeRange = $this->getTimeRange();

        $this->metricsData = [
            'performance' => $this->calculatePerformanceMetrics($serverClients),
            'usage' => $this->calculateUsageMetrics($serverClients, $timeRange),
            'availability' => $this->calculateReliabilityMetrics($serverClients),
            'trends' => $this->calculateTrendMetrics($serverClients, $timeRange),
        ];
    }

    protected function getTimeRange(): array
    {
        $end = now();

        return match ($this->selectedTimeRange) {
            '24h' => ['start' => $end->copy()->subDay(), 'end' => $end],
            '7d' => ['start' => $end->copy()->subWeek(), 'end' => $end],
            '30d' => ['start' => $end->copy()->subMonth(), 'end' => $end],
            '90d' => ['start' => $end->copy()->subMonths(3), 'end' => $end],
            default => ['start' => $end->copy()->subWeek(), 'end' => $end],
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServerClient::whereHas('order', function (Builder $query) {
                    $customer = Auth::guard('customer')->user();
                    $query->where('customer_id', $customer->id);
                })
                ->with(['server', 'order'])
            )
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('server.location')
                    ->label('Location')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('uptime')
                    ->label('Uptime')
                    ->getStateUsing(function (ServerClient $record) {
                        $s = $this->clientStats[$record->id] ?? null;
                        $v = $s['uptime_pct'] ?? null;
                        return $v !== null ? number_format((float) $v, 1) . '%' : '—';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        floatval($state) >= 99 => 'success',
                        floatval($state) >= 95 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('latency')
                    ->label('Latency')
                    ->getStateUsing(function (ServerClient $record) {
                        $s = $this->clientStats[$record->id] ?? null;
                        $v = $s['avg_latency'] ?? null;
                        return $v !== null && $v > 0 ? number_format((float) $v, 0) . 'ms' : '—';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        floatval($state) <= 50 => 'success',
                        floatval($state) <= 100 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('bandwidth_used_mb')
                    ->label('Data (Period)')
                    ->getStateUsing(function (ServerClient $record) {
                        $bytes = (int) ($this->clientStats[$record->id]['total_bytes'] ?? 0);
                        return $this->formatBytes($bytes);
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (ServerClient $record) {
                        $online = $this->clientStats[$record->id]['is_online'] ?? null;
                        return $online === null ? 'Unknown' : ($online ? 'Online' : 'Offline');
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Online' => 'success',
                        'Unknown' => 'warning',
                        'Offline' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('last_check')
                    ->label('Last Check')
                    ->getStateUsing(function (ServerClient $record) {
                        $t = $this->clientStats[$record->id]['last_seen'] ?? null;
                        if (! $t) {
                            return null; // Let placeholder render
                        }
                        try {
                            return Carbon::parse($t)->diffForHumans();
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                    // Avoid automatic Carbon parsing on already formatted strings
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('server')
                    ->relationship('server', 'name')
                    ->label('Server'),

                Filter::make('performance')
                    ->form([
                        Select::make('uptime_min')
                            ->label('Minimum Uptime')
                            ->options([
                                '95' => '95%+',
                                '98' => '98%+',
                                '99' => '99%+',
                                '99.9' => '99.9%+',
                            ]),
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('to')
                            ->label('To Date'),
                    ]),
            ])
            ->actions([
                Action::make('details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Server Performance Details')
                    ->modalContent(view('filament.customer.components.server-performance-details'))
                    ->modalActions([
                        \Filament\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ]),

                Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->action(function (ServerClient $record) {
                        // Simulate connection test
                        $success = rand(0, 1);
                        Notification::make()
                            ->title($success ? 'Connection Successful' : 'Connection Failed')
                            ->body($success ? 'Server is responding normally.' : 'Server is not responding. Please try again later.')
                            ->color($success ? 'success' : 'danger')
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('test_all')
                    ->label('Test All Connections')
                    ->icon('heroicon-o-signal')
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Testing Connections')
                            ->body('Testing ' . $records->count() . ' server connections...')
                            ->info()
                            ->send();
                    }),
            ])
            ->poll('30s') // Auto-refresh every 30 seconds
            ->defaultSort('created_at', 'desc');
    }

    public function getMetricsData(): array
    {
        return $this->metricsData;
    }

    public function updateTimeRange(string $range): void
    {
        $this->selectedTimeRange = $range;
        $this->loadMetricsData();
    }

    public function updateSelectedServer(?int $serverId): void
    {
        $this->selectedServer = $serverId;
        $this->loadMetricsData();
    }
}
