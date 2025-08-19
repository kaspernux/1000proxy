<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Concerns\HasPerformanceOptimizations;
use Filament\Resources\Resource;
use Filament\Tables; 
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\User as UserModel;
use App\Filament\Clusters\CustomerManagement\Resources\UserResource\Pages;

class UserResource extends Resource
{
    use HasPerformanceOptimizations;
    protected static ?string $model = UserModel::class;

    protected static ?string $cluster = CustomerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        // Only admin, manager, and support_manager can access staff-facing user listings; sales_support excluded.
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('last_login_at')->dateTime()->since(),
            ])
            ;

        return self::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-users',
                'heading' => 'No users found',
                'description' => 'Try a different search or filters.',
            ],
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}

namespace App\Filament\Clusters\CustomerManagement\Resources\UserResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Clusters\CustomerManagement\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
