<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\InboundClientIPResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\InboundClientIPResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInboundClientIP extends ViewRecord
{
    protected static string $resource = InboundClientIPResource::class;

    public function getTitle(): string
    {
        return 'IP Address Details';
    }

    public function getHeading(): string
    {
        return 'IP Address: ' . $this->record->ip;
    }

    protected function getHeaderActions(): array
    {
        return [
            // No edit action for customers - view only
        ];
    }
}
