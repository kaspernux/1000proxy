<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
