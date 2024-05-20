<?php

namespace App\Filament\Clusters\SiteManagement\Resources;

use App\Filament\Clusters\SiteManagement;
use App\Filament\Clusters\SiteManagement\Resources\InventoryLogResource\Pages;
use App\Filament\Clusters\SiteManagement\Resources\InventoryLogResource\RelationManagers;
use App\Models\InventoryLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryLogResource extends Resource
{
    protected static ?string $model = InventoryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = SiteManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('server_plan_id')
                    ->relationship('serverPlan', 'title')
                    ->required(),
                Forms\Components\TextInput::make('quantity_change')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('reason')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serverPlan.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_change')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->searchable(),
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
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListInventoryLogs::route('/'),
            'create' => Pages\CreateInventoryLog::route('/create'),
            'edit' => Pages\EditInventoryLog::route('/{record}/edit'),
        ];
    }
}
