<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\RelationManagers;
use App\Models\ServerInfo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerInfoResource extends Resource
{
    protected static ?string $model = ServerInfo::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $cluster = ServerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ucount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('remark')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('flag')
                    ->maxLength(255),
                Forms\Components\Toggle::make('active')
                    ->required(),
                Forms\Components\TextInput::make('state')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ucount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('flag')
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('state')
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
            'index' => Pages\ListServerInfos::route('/'),
            'create' => Pages\CreateServerInfo::route('/create'),
            'edit' => Pages\EditServerInfo::route('/{record}/edit'),
        ];
    }
}
