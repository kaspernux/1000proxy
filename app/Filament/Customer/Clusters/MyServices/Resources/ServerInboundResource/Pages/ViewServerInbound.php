<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerInboundResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerInboundResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewServerInbound extends ViewRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add any customer-specific actions here
        ];
    }

    public function getTitle(): string
    {
        return 'Inbound Details';
    }

    public function getHeading(): string
    {
        return 'Inbound Details';
    }
}
