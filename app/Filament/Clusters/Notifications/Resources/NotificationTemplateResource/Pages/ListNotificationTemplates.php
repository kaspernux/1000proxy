<?php

namespace App\Filament\Clusters\Notifications\Resources\NotificationTemplateResource\Pages;

use App\Filament\Clusters\Notifications\Resources\NotificationTemplateResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Filament\Actions;

class ListNotificationTemplates extends ListRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $canCreate = $user && in_array($user->role, ['admin','support_manager']);
        return $canCreate ? [
            Actions\CreateAction::make(),
        ] : [];
    }
}
