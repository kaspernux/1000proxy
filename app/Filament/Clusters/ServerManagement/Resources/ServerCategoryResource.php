<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\RelationManagers;
use App\Models\ServerCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerCategoryResource extends Resource
{
    protected static ?string $model = ServerCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $cluster = ServerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('options'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
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
            'index' => Pages\ListServerCategories::route('/'),
            'create' => Pages\CreateServerCategory::route('/create'),
            'edit' => Pages\EditServerCategory::route('/{record}/edit'),
        ];
    }
}