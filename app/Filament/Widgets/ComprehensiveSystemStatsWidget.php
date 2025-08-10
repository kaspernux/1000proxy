<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @deprecated Largely superseded by AdminDashboardStatsWidget + InfrastructureHealthWidget.
 * Retained temporarily; should be removed after confirming no external references.
 */
class ComprehensiveSystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return []; // suppressed
    }

    public function getColumns(): int
    {
        return 3;
    }

    protected function getPollingInterval(): ?string
    {
        return null; // no polling for deprecated widget
    }
}