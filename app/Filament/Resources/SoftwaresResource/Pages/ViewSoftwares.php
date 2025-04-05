<?php

namespace App\Filament\Resources\SoftwaresResource\Pages;

use App\Filament\Resources\SoftwaresResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwares extends ViewRecord
{
    protected static string $resource = SoftwaresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
