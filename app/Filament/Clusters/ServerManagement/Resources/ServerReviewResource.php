<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource\RelationManagers;
use App\Models\ServerReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerReviewResource extends Resource
    {
    protected static ?string $model = ServerReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 11;

    protected static ?string $cluster = ServerManagement::class;

    public static function getLabel(): string
    {
        return 'Reviews';
    }

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Review Details')
                        ->schema([
                            Forms\Components\RichEditor::make('comments')
                                ->columnSpanFull()
                                ->fileAttachmentsDirectory('ServerReview'),
                        ]),
                ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Review Details')
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
                                ->columnSpan(6),
                        ])->columns(2),
                ])->columnSpan(1),
            ])->columns(3);
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
            'index' => Pages\ListServerReviews::route('/'),
            'create' => Pages\CreateServerReview::route('/create'),
            'view' => Pages\ViewServerReview::route('/{record}'),
            'edit' => Pages\EditServerReview::route('/{record}/edit'),
        ];
        }
    }
