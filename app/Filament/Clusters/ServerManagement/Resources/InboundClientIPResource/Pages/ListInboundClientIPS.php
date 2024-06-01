<?php

namespace App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInboundClientIPS extends ListRecords
{
    protected static string $resource = InboundClientIPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
