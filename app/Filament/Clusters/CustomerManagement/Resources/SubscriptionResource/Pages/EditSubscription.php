<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
