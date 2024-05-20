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

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = ServerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('up')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('down')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('remark')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('enable')
                    ->required(),
                Forms\Components\DateTimePicker::make('expiryTime'),
                Forms\Components\TextInput::make('clientStats'),
                Forms\Components\TextInput::make('listen')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('port')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('protocol')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('settings')
                    ->required(),
                Forms\Components\TextInput::make('streamSettings')
                    ->required(),
                Forms\Components\TextInput::make('tag')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sniffing')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
            'edit' => Pages\EditServerInbound::route('/{record}/edit'),
        ];
    }
}
