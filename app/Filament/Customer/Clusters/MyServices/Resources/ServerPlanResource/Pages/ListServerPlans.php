<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerPlanResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerPlanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListServerPlans extends ListRecords
{
    protected static string $resource = ServerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers - plans are managed by admins
        ];
    }

    public function getTitle(): string
    {
        return 'Available Plans';
    }

    public function getHeading(): string
    {
        return 'Available Server Plans';
    }

    public function getSubheading(): string
    {
        return 'Browse and compare our available proxy server plans';
    }
}
