<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;
use App\Models\ServerClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\RichEditor;


class ServerClientResource extends Resource
{
    protected static ?string $model = ServerClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 6;

    public static function getLabel(): string
    {
        return 'Clients';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Client Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\RichEditor::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),
                            ])->columns(1),

                        Forms\Components\Section::make('Client Details')
                            ->schema([
                                Forms\Components\TextInput::make('client_id')
                                    ->label('Client ID')
                                    ->required()
                                    ->maxLength(36),
                                Forms\Components\TextInput::make('alter_id')
                                    ->label('Alter ID')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('limit_ip')
                                    ->label('IP Limit')
                                    ->numeric(),
                                Forms\Components\TextInput::make('total_gb')
                                    ->label('Total Capacity')
                                    ->prefix('Go')
                                    ->required()
                                    ->numeric(),
                                Forms\Components\TextInput::make('expiry_time')
                                    ->label('Expiry Time')
                                    ->required()
                                    ->numeric(),
                                Forms\Components\TextInput::make('tg_id')
                                    ->label('Telegram ID')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sub_id')
                                    ->label('Subscription ID')
                                    ->maxLength(255),
                            ])->columns(2),



                        Forms\Components\Section::make('QR-Codes')
                            ->schema([
                                Forms\Components\Textarea::make('qr_code_client')
                                    ->label('Client QR-code')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('qr_code_sub')
                                    ->label('Sub')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('qr_code_sub_json')
                                    ->label('Sub Json')
                                    ->columnSpanFull(),

                            ])
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Owner Details')
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer Name')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('server_inbound_id')
                                    ->label('Inbound ID')
                                    ->relationship('serverInbound', 'id')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                            ]),

                        Forms\Components\Section::make('Options')
                            ->schema([
                                Forms\Components\Toggle::make('enabled')
                                    ->label('Enable')
                                    ->required()
                                    ->default(true),
                                Forms\Components\Toggle::make('reset')
                                    ->label('Reset')
                                    ->required(),
                            ])
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('serverInbound.id')
                    ->label('Inbound ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('limit_ip')
                    ->label('IP Limit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_gb')
                    ->label('Total Capacity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_time')
                    ->label('Expiry Time')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tg_id')
                    ->label('Telegram ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sub_id')
                    ->label('Subscription ID')
                    ->searchable(),
                Tables\Columns\IconColumn::make('enabled')
                    ->label('Enable')
                    ->boolean(),
                Tables\Columns\IconColumn::make('reset')
                    ->label('Reset')
                    ->boolean(),
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
                // Add your filters here
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
            // Define your relations here
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
