<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServerClient;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Placeholder;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;


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
                                Forms\Components\Select::make('server_inbound_id')
                                    ->relationship('inbound', 'userId')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Proxy Username')
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(2),

                        Forms\Components\Section::make('Client Details')
                            ->schema([
                                Forms\Components\TextInput::make('flow')
                                    ->label('Flow')
                                    ->maxLength(36)
                                    ->default('None'),
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->default(null),
                                Forms\Components\TextInput::make('limitIp')
                                    ->label('IP Limit')
                                    ->numeric(),
                                Forms\Components\TextInput::make('totalGb')
                                    ->label('Total Capacity')
                                    ->prefix('Go')
                                    ->numeric(),
                                Forms\Components\DateTimePicker::make('expiryTime')
                                    ->label('Expiry Time'),
                                Forms\Components\TextInput::make('tgId')
                                    ->label('Telegram ID')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('subId')
                                    ->label('Subscription ID')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('QR-Codes')
                            ->schema([
                                Forms\Components\Placeholder::make('qr_code_client')
                                    ->label('Client QR-code')
                                    ->content(new HtmlString('<a href="https://filamentphp.com/docs">filamentphp.com</a>'))
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('qr_code_sub')
                                    ->label('Sub')
                                    ->content(new HtmlString('<a href="https://filamentphp.com/docs">filamentphp.com</a>'))
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('qr_code_sub_json')
                                    ->label('Sub Json')
                                    ->content(new HtmlString('<a href="https://filamentphp.com/docs">filamentphp.com</a>'))
                                    ->columnSpanFull(),

                            ])

                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serverInbound.id')
                    ->label('Inbound ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('flow')
                    ->searchable(),

                Tables\Columns\TextColumn::make('limitIp')
                    ->label('IP Limit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('totalGb')
                    ->label('Total Capacity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiryTime')
                    ->label('Expiry Time')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tgId')
                    ->label('Telegram ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subId')
                    ->label('Subscription ID')
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
                SelectFilter::make('server_inboud_id')
                    ->label('Inbounds')
                    ->relationship('inbound', 'userId'),
                SelectFilter::make('email')
                    ->label('Proxy User'),

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