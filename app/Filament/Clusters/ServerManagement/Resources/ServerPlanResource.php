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

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Plans';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Server Plan Details')
                        ->schema([
                            Forms\Components\Select::make('server_category_id')
                                ->relationship('category', 'name') // 'category' matches the method name in the model
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->prefix('$'),

                            Forms\Components\Select::make('type')
                                ->required()
                                ->options([
                                    'single' => 'Single',
                                    'multiple' => 'Multiple',
                                    'dedicated' => 'Dedicated',
                                ]),

                            Forms\Components\TextInput::make('days')
                                ->required()
                                ->numeric(),

                            Forms\Components\TextInput::make('volume')
                                ->required()
                                ->prefix('Go')
                                ->numeric(),
                        ])->columns(2),
                ])->columnSpan(2),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
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
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
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
