<?php
namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Pages\Page;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class BusinessIntelligenceResource extends Resource
{
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Business Intelligence';

    protected static ?string $slug = 'business-intelligence';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = 'Analytics';

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages\BusinessIntelligenceDashboard::route('/'),
            'revenue' => \App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages\RevenueAnalytics::route('/revenue'),
            'users' => \App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages\UserAnalytics::route('/users'),
            'servers' => \App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages\ServerAnalytics::route('/servers'),
            'insights' => \App\Filament\Admin\Resources\BusinessIntelligenceResource\Pages\InsightsReport::route('/insights'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        // Allow reporting roles
        return $user->isAdmin() || $user->isManager() || $user->isSupportManager() || (method_exists($user, 'isAnalyst') && $user->isAnalyst());
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->isAdmin() || $user->isManager() || $user->isSupportManager() || (method_exists($user, 'isAnalyst') && $user->isAnalyst());
    }
}

