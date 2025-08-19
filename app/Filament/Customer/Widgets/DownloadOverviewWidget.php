<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DownloadableItem;
use Illuminate\Support\Facades\Auth;

class DownloadOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $customerId = Auth::guard('customer')->id();

        $count = DownloadableItem::query()
            // only items whose server has an inbound client for this customer
            ->whereHas('server.inbounds.clients', fn ($q) =>
                $q->where('email', 'like', "%#ID {$customerId}")
            )
            ->count();

        return [
            Stat::make('Available Files', $count)
                ->description('Files included with your services')
                ->descriptionIcon('heroicon-m-document-text')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->extraAttributes(['class' => 'kp-stat kp-stat--info']),
        ];
    }
}
