<?php

namespace App\Filament\Clusters\Shop\Resources\PaymentsResource\Pages;

use App\Filament\Clusters\Shop\Resources\PaymentsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayments extends CreateRecord
{
    protected static string $resource = PaymentsResource::class;
}
