<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\RelationManagers;
use App\Models\ServerInbound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerInboundResource extends Resource
{
    protected static ?string $model = ServerInbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $cluster = ServerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('up')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('down')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('remark')
                    ->maxLength(255),
                Forms\Components\Toggle::make('enable')
                    ->required(),
                Forms\Components\DateTimePicker::make('expiryTime'),
                Forms\Components\TextInput::make('clientStats'),
                Forms\Components\TextInput::make('listen')
                    ->maxLength(255),
                Forms\Components\TextInput::make('port')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('protocol')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('settings'),
                Forms\Components\TextInput::make('streamSettings'),
                Forms\Components\TextInput::make('tag')
                    ->maxLength(100),
                Forms\Components\TextInput::make('sniffing'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('up')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('down')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')
                    ->searchable(),
                Tables\Columns\IconColumn::make('enable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expiryTime')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('listen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('protocol')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tag')
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
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListServerInbounds::route('/'),
            'create' => Pages\CreateServerInbound::route('/create'),
            'view' => Pages\ViewServerInbound::route('/{record}'),
            'edit' => Pages\EditServerInbound::route('/{record}/edit'),
        ];
    }
}
