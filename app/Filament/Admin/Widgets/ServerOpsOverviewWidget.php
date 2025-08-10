<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Server;
use App\Models\ServerClient;
use Illuminate\Support\Facades\Cache;

class ServerOpsOverviewWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.server-ops-overview';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Server::class) ?? false;
    }

    protected function getViewData(): array
    {
        $data = Cache::remember('server_ops_overview', 120, function () {
            return [
                'online' => Server::where('status', 'online')->count(),
                'offline' => Server::where('status', 'offline')->count(),
                'maintenance' => Server::where('status', 'maintenance')->count(),
                'avg_uptime' => round(Server::avg('uptime_percentage') ?? 0, 2),
                'active_clients' => ServerClient::where('status', 'active')->count(),
                'total_bandwidth_gb' => round((Server::sum('total_traffic_mb') ?? 0) / 1024, 2),
            ];
        });
        return $data;
    }
}
