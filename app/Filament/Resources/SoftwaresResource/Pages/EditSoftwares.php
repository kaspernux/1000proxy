<?php

namespace App\Filament\Resources\SoftwaresResource\Pages;

use App\Filament\Resources\SoftwaresResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftwares extends EditRecord
{
    protected static string $resource = SoftwaresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
