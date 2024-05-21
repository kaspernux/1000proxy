<?php

namespace App\Filament\Clusters\Shop\Resources\PaymentsResource\Pages;

use App\Filament\Clusters\Shop\Resources\PaymentsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
