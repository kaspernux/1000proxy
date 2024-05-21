<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\GiftListResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\GiftListResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGiftList extends CreateRecord
{
    protected static string $resource = GiftListResource::class;
}
