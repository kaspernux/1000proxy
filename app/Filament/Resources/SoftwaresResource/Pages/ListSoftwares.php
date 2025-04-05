<?php

namespace App\Filament\Resources\SoftwaresResource\Pages;

use App\Filament\Resources\SoftwaresResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoftwares extends ListRecords
{
    protected static string $resource = SoftwaresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
