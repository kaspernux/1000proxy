<?php

namespace App\Filament\Clusters\StaffManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\StaffManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Staff Member'),
        ];
    }

    public function getTitle(): string
    {
        return 'Staff Member Details';
    }

    public function getSubheading(): string
    {
        return 'View and manage staff member information';
    }
}
