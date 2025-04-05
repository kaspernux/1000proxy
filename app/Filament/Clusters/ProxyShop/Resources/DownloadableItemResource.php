<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\RelationManagers;
use App\Models\DownloadableItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DownloadableItemResource extends Resource
    {
    protected static ?string $model = DownloadableItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $cluster = ProxyShop::class;

    public static function getLabel(): string
    {
        return 'Downloadable Files';
    }

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Group::make([
                    Section::make('Download Details')
                        ->schema([
                            Forms\Components\TextInput::make('server_id')
                                ->required()
                                ->numeric()
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('file_url')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(3),
                        ])
                        ->columns(4),
                    Section::make('Limits and Expiration')
                        ->schema([
                            Forms\Components\TextInput::make('download_limit')
                                ->numeric()
                                ->columnSpan(2),
                            Forms\Components\DateTimePicker::make('expiration_time')
                                ->columnSpan(2),
                        ])
                        ->columns(4),
                ])->columnSpanFull(),
            ])->columns(4);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('download_limit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiration_time')
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
            'index' => Pages\ListDownloadableItems::route('/'),
            'create' => Pages\CreateDownloadableItem::route('/create'),
            'view' => Pages\ViewDownloadableItem::route('/{record}'),
            'edit' => Pages\EditDownloadableItem::route('/{record}/edit'),
        ];
        }
    }
