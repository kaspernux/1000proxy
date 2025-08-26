<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class TeamChat extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|UnitEnum|null $navigationGroup = 'Communication';
    protected static ?int $navigationSort = 10;
    protected static ?string $title = 'Team & Support Chat';
    protected static ?string $slug = 'team-chat';

    protected string $view = 'filament.admin.pages.team-chat';

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
