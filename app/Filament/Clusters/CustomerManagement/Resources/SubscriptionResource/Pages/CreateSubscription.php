<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;
}
