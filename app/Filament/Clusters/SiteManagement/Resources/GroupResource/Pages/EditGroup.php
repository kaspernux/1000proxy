<?php

namespace App\Filament\Clusters\SiteManagement\Resources\GroupResource\Pages;

use App\Filament\Clusters\SiteManagement\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
