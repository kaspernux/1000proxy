<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Server;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;


class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Servers';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Basic Information')->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('server_category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('server_brand_id')
                            ->relationship('brand', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                    ])->columns(2),

                    Section::make('Description')->schema([
                        Forms\Components\MarkdownEditor::make('description')
                            ->fileAttachmentsDirectory('servers'),
                    ]),
                ])->columnSpan(2),
                Group::make()->schema([
                    Section::make('Country Flag')->schema([
                        Forms\Components\FileUpload::make('flag')
                            ->image()
                            ->directory('servers'),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'up' => 'Up',
                                'down' => 'Down',
                                'paused' => 'Paused',
                            ]),
                    ])->columns(1),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('flag')
                    ->label('Flag')
                    ->url(fn ($record) => $record->flag),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable(),
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
                SelectFilter::make('server_category_id')
                    ->label('Categories')
                    ->relationship('category', 'name'),
                SelectFilter::make('country')
                    ->label('Countries'),
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
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 5 ? 'success':'danger';
    }
}
