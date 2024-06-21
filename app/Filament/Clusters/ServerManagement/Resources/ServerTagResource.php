<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerTagResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerTagResource\RelationManagers;
use App\Models\ServerTag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerTagResource extends Resource
    {
    protected static ?string $model = ServerTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 4;

    public static function getLabel(): string
    {
        return 'Tags';
    }

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Group::make([
                    Section::make('Tag Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                        ]),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Server Association')
                        ->schema([
                            Forms\Components\Select::make('server_id')
                                ->relationship('server', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('server.name')
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
            'index' => Pages\ListServerTags::route('/'),
            'create' => Pages\CreateServerTag::route('/create'),
            'view' => Pages\ViewServerTag::route('/{record}'),
            'edit' => Pages\EditServerTag::route('/{record}/edit'),
        ];
        }
    }