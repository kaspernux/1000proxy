<?php

namespace App\Filament\Clusters\ServerManagement\Resources\SoftwaresResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\SoftwaresResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftwares extends EditRecord
{
    protected static string $resource = SoftwaresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
