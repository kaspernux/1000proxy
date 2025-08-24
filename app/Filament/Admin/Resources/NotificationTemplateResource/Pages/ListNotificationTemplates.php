<?php

namespace App\Filament\Admin\Resources\NotificationTemplateResource\Pages;

use App\Filament\Admin\Resources\NotificationTemplateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;

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
