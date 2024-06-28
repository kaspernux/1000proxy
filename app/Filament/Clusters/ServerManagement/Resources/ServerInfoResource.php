<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;
use App\Models\ServerInfo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;

class ServerInfoResource extends Resource
{
    protected static ?string $model = ServerInfo::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 9;

    public static function getLabel(): string
    {
        return 'Info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Server Infos')->schema([
                        Select::make('server_id')
                            ->relationship('server', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('flag')
                            ->maxLength(255),
                        TextInput::make('ucount')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Select::make('state')
                            ->required()
                            ->options([
                                'up' => 'Up',
                                'down' => 'Down',
                                'paused' => 'Paused',
                            ]),
                    ])->columns(2),
                    Section::make('Details')->schema([
                        RichEditor::make('remark')
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('ServerInfo'),
                    ])
                ])->columnSpan(2),
                Group::make()->schema([
                    Section::make('Enable')->schema([
                        Toggle::make('active')
                            ->required(),
                    ])
                ])->columnSpan(1)
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListServerInfos::route('/'),
            'create' => Pages\CreateServerInfo::route('/create'),
            'view' => Pages\ViewServerInfo::route('/{record}'),
            'edit' => Pages\EditServerInfo::route('/{record}/edit'),
        ];
    }
}
