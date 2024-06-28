<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource\RelationManagers;
use App\Models\ServerConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;


class ServerConfigResource extends Resource
{
    protected static ?string $model = ServerConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 8;

    public static function getLabel(): string
    {
        return 'Settings';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Server Details')
                        ->schema([
                            Forms\Components\Select::make('server_id')
                                ->relationship('server', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\TextInput::make('type')
                                ->maxLength(255),
                        ])->columns(2),
                    Forms\Components\Section::make('Headers and Security')
                        ->schema([
                            Forms\Components\TextInput::make('panel_url')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('ip')
                                ->label('IP')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('sni')
                                ->label('SNI')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('header_type')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('request_header')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('response_header')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('security')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('tlsSettings')
                                ->maxLength(255),
                        ])->columns(2),

                    Forms\Components\Section::make('Port and Reality Settings')
                        ->schema([
                            Forms\Components\TextInput::make('port_type')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('reality')
                                ->maxLength(255),
                        ])->columns(2)
                ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Authentication and Type')
                        ->schema([
                            Forms\Components\TextInput::make('username')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                                ->maxLength(255),
                        ]),


                ])->columnSpan(1)
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('panel_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sni')
                    ->searchable(),
                Tables\Columns\TextColumn::make('header_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('request_header')
                    ->searchable(),
                Tables\Columns\TextColumn::make('response_header')
                    ->searchable(),
                Tables\Columns\TextColumn::make('security')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tlsSettings')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reality')
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
            'index' => Pages\ListServerConfigs::route('/'),
            'create' => Pages\CreateServerConfig::route('/create'),
            'view' => Pages\ViewServerConfig::route('/{record}'),

           'edit' => Pages\EditServerConfig::route('/{record}/edit'),
        ];
    }
}
