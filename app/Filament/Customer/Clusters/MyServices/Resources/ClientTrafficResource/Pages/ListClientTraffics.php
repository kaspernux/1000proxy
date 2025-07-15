<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ClientTrafficResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ClientTrafficResource;
use Filament\Resources\Pages\ListRecords;

class ListClientTraffics extends ListRecords
{
    protected static string $resource = ClientTrafficResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers - traffic is automatically tracked
        ];
    }

    public function getTitle(): string
    {
        return 'Traffic Monitor';
    }

    public function getHeading(): string
    {
        return 'My Client Traffic';
    }

    public function getSubheading(): string
    {
        return 'Monitor your proxy client traffic usage and statistics';
    }
}
