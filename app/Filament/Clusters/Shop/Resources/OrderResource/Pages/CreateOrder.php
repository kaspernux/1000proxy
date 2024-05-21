<?php

namespace App\Filament\Clusters\Shop\Resources\OrderResource\Pages;

use App\Filament\Clusters\Shop\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}