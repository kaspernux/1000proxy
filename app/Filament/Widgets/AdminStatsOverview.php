<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use App\Models\Order;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @deprecated Replaced by AdminDashboardStatsWidget using MetricsAggregator.
 * Retained temporarily to avoid reference breaks; will be removed after rollout.
 */
class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 9999; // ensure last

    protected function getStats(): array
    {
        // Intentionally empty to suppress legacy widget output.
        return [];
    }

    protected function shouldRegisterNavigation(): bool
    {
        return false;
    }
}