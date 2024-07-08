<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\ServerCategory;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Split;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\RelationManagers;


class ServerCategoryResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Category';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Grid::make()
                        ->schema([
                            Section::make([
                                TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                            if ($operation === 'create') {
                                                $set('slug', Str::slug($state));
                                            }
                                        }),
                                TextInput::make('slug')
                                    ->required()
                                    ->disabled()
                                    ->unique(ServerCategory::class, 'slug', ignoreRecord: true),

                                FileUpload::make('image')
                                    ->image()
                                    ->directory('server_categories')
                                    ->columnSpanFull(),
                            ])->columns(2),
                            Section::make([
                                Toggle::make('is_active')
                                    ->required()
                                    ->default(true),
                            ])->grow(false),
                        ]),
                ])
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->url(fn ($record) => $record->image),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Is Active')
                    ->boolean(),
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
            'index' => Pages\ListServerCategories::route('/'),
            'create' => Pages\CreateServerCategory::route('/create'),
            'view' => Pages\ViewServerCategory::route('/{record}'),
            'edit' => Pages\EditServerCategory::route('/{record}/edit'),
        ];


  }
}