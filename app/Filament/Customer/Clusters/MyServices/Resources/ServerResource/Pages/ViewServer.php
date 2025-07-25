<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerResource;
use Filament\Resources\Pages\ViewRecord;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit/delete actions for customers
        ];
    }

    public function getTitle(): string
    {
        return 'Server: ' . $this->record->name;
    }

    public function getHeading(): string
    {
        return 'Server Details';
    }
}
