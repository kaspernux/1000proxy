<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ServerPlan;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Plans';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make('Server Plan Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->disabled()
                            ->unique(ServerPlan::class, 'slug', ignoreRecord: true),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('volume')
                            ->required()
                            ->default('500')
                            ->prefix('GB')
                            ->numeric(),
                        TextInput::make('days')
                            ->required()
                            ->default('30')
                            ->numeric(),
                        TextInput::make('capacity')
                            ->label('Max Clients')
                            ->default('1')
                            ->required()
                            ->numeric(),
                    ])->columns(2),
                Section::make('Description')
                    ->schema([
                        MarkdownEditor::make('description')
                            ->label('')
                            ->fileAttachmentsDirectory('serverPlans')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'textColor',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                    ]),
                Section::make('Product Image')
                    ->schema([
                        FileUpload::make('product_image')
                            ->image()
                            ->columnSpan(2)
                            ->directory('server_plans')
                    ]),
            ])->columnSpan(2),
            Group::make([
                Section::make('Server Details')
                    ->schema([
                        Select::make('type')
                            ->required()
                            ->options([
                                'single' => 'Single',
                                'multiple' => 'Multiple',
                                'dedicated' => 'Dedicated',
                                'branded' => 'Branded',
                            ]),
                        Select::make('server_id')
                            ->label('Server')
                            ->relationship('server', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),
                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                        Toggle::make('in_stock')
                            ->label('In Stock')
                            ->default(true),
                        Toggle::make('on_sale')
                            ->label('On Sale')
                            ->default(true),
                    ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('product_image')
                    ->label('Product Image'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('server.name')
                    ->sortable(),
                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('type'),
                TextColumn::make('days')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('volume')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                IconColumn::make('in_stock')
                    ->label('In Stock')
                    ->boolean(),
                IconColumn::make('on_sale')
                    ->label('On Sale')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('server')
                    ->label('Servers')
                    ->relationship('server', 'name'),
                SelectFilter::make('price')
                    ->label('Price'),

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
            // Define relations here if any
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerPlans::route('/'),
            'create' => Pages\CreateServerPlan::route('/create'),
            'view' => Pages\ViewServerPlan::route('/{record}'),
            'edit' => Pages\EditServerPlan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'server.name'];
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 10 ? 'success' : 'danger';
    }
}