<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;
use App\Models\ServerInbound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;


class ServerInboundResource extends Resource
{
    protected static ?string $model = ServerInbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'server_id';

    public static function getLabel(): string
    {
        return 'Configurations';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Server Details')
                        ->schema([
                            Select::make('server_id')
                                ->relationship('server', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            TextInput::make('user_id')
                                ->required()
                                ->numeric(),

                            TextInput::make('remark')
                                ->maxLength(255),

                            Toggle::make('enable')
                                ->required()
                                ->default(true),
                        ])->columns(2),

                    Section::make('Connection Details')
                        ->schema([
                            TextInput::make('listen')
                                ->maxLength(255),

                            TextInput::make('port')
                                ->required()
                                ->numeric(),
                        ])->columns(2),

                    Section::make('Protocol and Settings')
                        ->schema([
                            TextInput::make('protocol')
                                ->required()
                                ->maxLength(50),

                            TextInput::make('settings')
                                ->json(),

                            TextInput::make('stream_settings')
                                ->json(),

                            Toggle::make('sniffing')
                                ->default(false),
                        ])->columns(2),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Additional Info')
                        ->schema([
                            TextInput::make('up')
                                ->required()
                                ->numeric()
                                ->default(0),

                            TextInput::make('down')
                                ->required()
                                ->numeric()
                                ->default(0),

                            TextInput::make('total')
                                ->required()
                                ->numeric()
                                ->default(0),

                            DatePicker::make('expiry_time')
                                ->required(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total Clients')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('protocol')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('port')
                    ->sortable(),

                Tables\Columns\TextColumn::make('up')
                    ->sortable(),

                Tables\Columns\TextColumn::make('down')
                    ->sortable(),

                Tables\Columns\IconColumn::make('enable')
                    ->label('Enabled')
                    ->boolean(),

                Tables\Columns\TextColumn::make('expiry_time')
                    ->dateTime()
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
                // Add filters if needed
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
            'index' => Pages\ListServerInbounds::route('/'),
            'create' => Pages\CreateServerInbound::route('/create'),
            'view' => Pages\ViewServerInbound::route('/{record}'),
            'edit' => Pages\EditServerInbound::route('/{record}/edit'),
        ];
    }
}
