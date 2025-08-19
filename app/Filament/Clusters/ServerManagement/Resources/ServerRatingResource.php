<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\RelationManagers;
use App\Models\ServerRating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Concerns\HasPerformanceOptimizations;

class ServerRatingResource extends Resource
    {
    use HasPerformanceOptimizations;
    protected static ?string $model = ServerRating::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    public static function getLabel(): string
    {
        return 'Ratings';
    }

    public static function form(Schema $schema): Schema
        {
        return $schema
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Server and Customer Details')
                        ->description('Select the server and customer this rating applies to')
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
                        ->description('Provide a rating from 1 to 5 (decimals allowed)')
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
        $table = $table
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
            ])
            ->defaultSort('created_at', 'desc');

        return self::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-star',
                'heading' => 'No ratings found',
                'description' => 'Ratings appear after customers review a server.',
            ],
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
