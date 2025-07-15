<?php

namespace App\Filament\Clusters\StaffManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\StaffManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\ButtonAction;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Staff Member')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add staff statistics widgets here if needed
        ];
    }

    public function getTitle(): string
    {
        return 'Staff Management';
    }

    public function getSubheading(): string
    {
        return 'Manage internal staff accounts and roles';
    }
}
