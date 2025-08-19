<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables; 
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Concerns\HasPerformanceOptimizations;
use BackedEnum;

class StaffUsers extends Page implements HasTable
{
    use InteractsWithTable, HasPerformanceOptimizations;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff Users';
    protected static ?string $title = 'Staff Users';
    protected static ?string $slug = 'staff-management/users';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.admin.pages.staff-users';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isManager() || $user->isSupportManager());
    }

    public function table(Table $table): Table
    {
        return self::applyTablePreset(
            $table
                ->query(
                    User::query()->whereIn('role', ['admin','manager','support_manager','sales_support','analyst'])
                )
                ->columns([
                    TextColumn::make('name')
                        ->label('Name')
                        ->searchable()
                        ->sortable()
                        ->description(fn (User $record) => $record->email, position: 'below')
                        ->weight('bold'),
                    TextColumn::make('role')
                        ->label('Role')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'admin' => 'danger',
                            'manager' => 'warning',
                            'support_manager' => 'info',
                            'sales_support' => 'gray',
                            'analyst' => 'success',
                            default => 'gray',
                        })
                        ->sortable(),
                    IconColumn::make('is_active')
                        ->label('Active')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->sortable(),
                    TextColumn::make('last_login_at')
                        ->label('Last Login')
                        ->dateTime('M d, Y H:i')
                        ->since()
                        ->sortable(),
                    TextColumn::make('created_at')
                        ->label('Registered')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('role')
                        ->label('Role')
                        ->options([
                            'admin' => 'Administrator',
                            'manager' => 'Manager',
                            'support_manager' => 'Support Manager',
                            'sales_support' => 'Sales Support',
                            'analyst' => 'Analyst',
                        ]),
                    SelectFilter::make('is_active')
                        ->label('Status')
                        ->options([
                            '1' => 'Active',
                            '0' => 'Inactive',
                        ]),
                ])
                ->defaultSort('created_at', 'desc')
            , [
                'defaultPage' => 25,
                'empty' => [
                    'icon' => 'heroicon-o-users',
                    'heading' => 'No staff users',
                    'description' => 'Use Staff Management to add users or adjust filters.',
                ],
            ]
        );
    }
}
