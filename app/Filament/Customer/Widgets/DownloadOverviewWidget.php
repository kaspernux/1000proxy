<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\DownloadableItem;
use Illuminate\Support\Facades\Auth;

class DownloadOverviewWidget extends StatsOverviewWidget
{
    // must be non-static in v3:
    protected ?string $heading = 'My Downloads';

    protected function getCards(): array
    {
        $customerId = Auth::guard('customer')->id();

        $count = DownloadableItem::query()
            // only items whose server has an inbound client for this customer
            ->whereHas('server.inbounds.clients', fn ($q) =>
                $q->where('email', 'like', "%#ID {$customerId}")
            )
            ->count();

        return [
            Card::make('Available Files', $count)
                ->description('Files included with your services')
                ->icon('heroicon-o-document-text'),
        ];
    }
}
