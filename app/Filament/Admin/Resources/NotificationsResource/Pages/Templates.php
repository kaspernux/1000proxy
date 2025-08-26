<?php

namespace App\Filament\Admin\Resources\NotificationsResource\Pages;

use App\Filament\Admin\Resources\NotificationsResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class Templates extends ListRecords
{
    protected static string $resource = NotificationsResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $can = $user && ($user->hasRole('admin') || in_array($user->role ?? null, ['admin','support_manager']));

        $actions = [];
        if ($can) {
            $actions[] = Actions\CreateAction::make();
        }

        // Quick links to subpages for visibility
        $actions[] = Actions\Action::make('go_broadcasts')
            ->label('Broadcasts')
            ->icon('heroicon-o-megaphone')
            ->url(fn () => \App\Filament\Admin\Resources\NotificationsResource::getUrl('broadcasts'));

        $actions[] = Actions\Action::make('go_telegram_templates')
            ->label('Telegram Templates')
            ->icon('heroicon-o-document-text')
            ->url(fn () => \App\Filament\Admin\Resources\NotificationsResource::getUrl('telegram-templates'));

        $actions[] = Actions\Action::make('go_push_notifications')
            ->label('Push Notifications')
            ->icon('heroicon-o-bell')
            ->url(fn () => \App\Filament\Admin\Resources\NotificationsResource::getUrl('push-notifications'));

        return $actions;
    }
}
