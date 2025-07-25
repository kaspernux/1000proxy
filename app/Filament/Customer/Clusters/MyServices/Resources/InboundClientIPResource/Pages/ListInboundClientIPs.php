<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\InboundClientIPResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\InboundClientIPResource;
use Filament\Resources\Pages\ListRecords;

class ListInboundClientIPs extends ListRecords
{
    protected static string $resource = InboundClientIPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers - IPs are automatically assigned
        ];
    }

    public function getTitle(): string
    {
        return 'Assigned IP Addresses';
    }

    public function getHeading(): string
    {
        return 'Your Proxy IP Addresses';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add widgets here if needed
        ];
    }
}
