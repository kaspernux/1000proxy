<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ClientTrafficResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ClientTrafficResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewClientTraffic extends ViewRecord
{
    protected static string $resource = ClientTrafficResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add any customer-specific actions here
        ];
    }

    public function getTitle(): string
    {
        return 'Traffic Details';
    }

    public function getHeading(): string
    {
        return 'Client Traffic Details';
    }
}
