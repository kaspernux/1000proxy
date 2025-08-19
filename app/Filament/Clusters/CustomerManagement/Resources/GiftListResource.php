<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\GiftListResource\Pages;
use App\Models\GiftList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use BackedEnum;

class GiftListResource extends Resource
{
    protected static ?string $model = GiftList::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $cluster = CustomerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make([
                    Section::make('Server Information')
                        ->schema([
                            Forms\Components\Select::make('server_id')
                                ->relationship('server', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(3),
                        ]),
                ])->columnSpan(1),
                Group::make([
                    Section::make('Gift List Details')
                        ->schema([
                            Forms\Components\TextInput::make('volume')
                                ->required()
                                ->numeric(),
                            Forms\Components\DateTimePicker::make('day')
                                ->maxWidth(255)
                                ->required(),
                            Forms\Components\TextInput::make('offset')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('server_offset')
                                ->required()
                                ->numeric(),
                        ])->columns(2),
                ])->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('volume')
                    ->sortable(),
                Tables\Columns\TextColumn::make('day')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('offset')
                    ->sortable(),
                Tables\Columns\TextColumn::make('server_offset')
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
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListGiftLists::route('/'),
            'create' => Pages\CreateGiftList::route('/create'),
            'view' => Pages\ViewGiftList::route('/{record}'),
            'edit' => Pages\EditGiftList::route('/{record}/edit'),
        ];
    }
}