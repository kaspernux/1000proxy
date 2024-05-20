<?php

namespace App\Filament\Clusters\DigiShop\Resources\SubscriptionResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
