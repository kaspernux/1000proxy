<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServerClient;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ServerManagement;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\RelationManagers;

class ServerClientResource extends Resource
{
    use LivewireAlert;

    protected static ?string $model = ServerClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'PROXY SETTINGS';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'server_id';

    public static function getLabel(): string
    {
        return 'Clients';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('server_inbound_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->maxLength(255),
                Forms\Components\TextInput::make('flow')
                    ->required()
                    ->maxLength(255)
                    ->default('None'),
                Forms\Components\TextInput::make('limitIp')
                    ->numeric(),
                Forms\Components\TextInput::make('totalGB')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('expiryTime'),
                Forms\Components\TextInput::make('tgId')
                    ->maxLength(255),
                Forms\Components\TextInput::make('subId')
                    ->maxLength(255),
                Forms\Components\Toggle::make('enable')
                    ->required(),
                Forms\Components\TextInput::make('reset')
                    ->numeric(),
                Forms\Components\Textarea::make('qr_code_sub')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('qr_code_sub_json')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('qr_code_client')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server_inbound_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('flow')
                    ->searchable(),
                Tables\Columns\TextColumn::make('limitIp')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('totalGB')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiryTime')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tgId')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subId')
                    ->searchable(),
                Tables\Columns\IconColumn::make('enable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reset')
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
                SelectFilter::make('server_inbound_id')
                    ->label('Inboun ID')
                    ->relationship('inbound', 'id'),
                SelectFilter::make('email')
                    ->label('User'),
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
            'index' => Pages\ListServerClients::route('/'),
            'create' => Pages\CreateServerClient::route('/create'),
            'view' => Pages\ViewServerClient::route('/{record}'),
            'edit' => Pages\EditServerClient::route('/{record}/edit'),
        ];
    }
}