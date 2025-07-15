<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\CreateAction;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New User')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Users & Administrators';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }
}
