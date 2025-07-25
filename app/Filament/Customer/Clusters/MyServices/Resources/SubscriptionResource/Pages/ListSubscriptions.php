<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\SubscriptionResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers - subscriptions are created through orders
        ];
    }

    public function getTitle(): string
    {
        return 'My Subscriptions';
    }

    public function getHeading(): string
    {
        return 'My Subscriptions';
    }

    public function getSubheading(): string
    {
        return 'View and manage your subscription plans';
    }
}
