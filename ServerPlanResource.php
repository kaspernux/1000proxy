<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;
use App\Models\ServerPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ServerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('General Information')
                            ->schema([
                                TextInput::make('name')
                                    ->maxLength(255)
                                    ->columns(2),
                                TextInput::make('days')
                                    ->required()
                                    ->numeric()
                                    ->columns(2),
                                TextInput::make('volume')
                                    ->required()
                                    ->numeric(),
                                TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->columns(2),
                                ToggleButtons::make('type')
                                    ->required()
                                    ->options([
                                        'single' => 'Single',
                                        'multiple' => 'Multiple',
                                        'dedicated' => 'Dedicated',
                                    ])
                                    ->colors([
                                        'single' => 'success',
                                        'multiple' => 'warning',
                                        'dedicated' => 'success',
                                    ])
                                    ->icons([
                                        'single' => 'heroicon-o-star',
                                        'multiple' => 'heroicon-o-sparkles',
                                        'dedicated' => 'heroicon-o-check-badge',
                                    ])
                                    ->inline()
                                    ->gridDirection('row'),
                            ])
                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Server Details')
                            ->schema([
                                Select::make('server_category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ])
                    ->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('volume')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add filters if needed
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerPlans::route('/'),
            'create' => Pages\CreateServerPlan::route('/create'),
            'view' => Pages\ViewServerPlan::route('/{record}'),
            'edit' => Pages\EditServerPlan::route('/{record}/edit'),
        ];
    }
}
