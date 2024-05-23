<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\GiftListResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\GiftListResource\RelationManagers;
use App\Models\GiftList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GiftListResource extends Resource
    {
    protected static ?string $model = GiftList::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $cluster = CustomerManagement::class;

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Group::make([
                    Section::make('Server Information')
                        ->schema([
                            Forms\Components\Select::make('server_id')
                                ->relationship('server', 'name')
                                ->required()
                                ->columnSpan(1),
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('volume')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('offset')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('server_offset')
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
            'index' => Pages\ListGiftLists::route('/'),
            'create' => Pages\CreateGiftList::route('/create'),
            'view' => Pages\ViewGiftList::route('/{record}'),
            'edit' => Pages\EditGiftList::route('/{record}/edit'),
        ];
        }
    }