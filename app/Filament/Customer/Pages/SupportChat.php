<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class SupportChat extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';
    protected static string|UnitEnum|null $navigationGroup = 'Support';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Support Chat';
    protected static ?string $slug = 'support-chat';

    protected string $view = 'filament.customer.pages.support-chat';

    public static function canAccess(): bool
    {
        return auth('customer')->check();
    }
}
