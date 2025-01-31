<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\RelationManagers;
use App\Models\ServerRating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerRatingResource extends Resource
    {
    protected static ?string $model = ServerRating::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = ServerManagement::class;

    public static function getLabel(): string
    {
        return 'Ratings';
    }

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Server and Customer Details')
                        ->schema([
                            Forms\Components\Select::make('server_id')
                                ->relationship('server', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->columnSpan(6),
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),
                        ])->columns(2),
                ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Rating Information')
                        ->schema([
                            Forms\Components\TextInput::make('rating')
                                ->required()
                                ->numeric(),
                        ]),
                ])->columnSpan(2),
            ])->columns(4);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
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
            'index' => Pages\ListServerRatings::route('/'),
            'create' => Pages\CreateServerRating::route('/create'),
            'view' => Pages\ViewServerRating::route('/{record}'),
            'edit' => Pages\EditServerRating::route('/{record}/edit'),
        ];
        }
    }
