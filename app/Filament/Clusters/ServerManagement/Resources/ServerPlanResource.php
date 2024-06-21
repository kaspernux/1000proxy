<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\RelationManagers;
use App\Models\ServerPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;



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
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
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
                            ->prefix('Go')
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
                            ->columnSpanFull(),
                    ]),
                Section::make('Product Image')
                    ->schema([
                            Forms\Components\FileUpload::make('product_image')
                                ->image()
                                ->columnSpan(2),
                        ]),
            ])
            ->columnSpan(2),

            Group::make([
                Section::make('Server Details')
                    ->schema([
                        Select::make('type')
                            ->required()
                            ->options([
                                'single' => 'Single',
                                'multiple' => 'Multiple',
                                'dedicated' => 'Dedicated',
                            ]),
                        Select::make('server_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('server_brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                ]),

                Section::make('Server Details')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(false),
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
                Tables\Columns\ImageColumn::make('product_image')
                    ->label('Product Image'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('days')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('volume')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                Tables\Columns\IconColumn::make('in_stock')
                    ->label('In Stock')
                    ->boolean(),
                Tables\Columns\IconColumn::make('on_sale')
                    ->label('On Sale')
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
                SelectFilter::make('category')
                    ->label('Categories')
                    ->relationship('category', 'name'),
                SelectFilter::make('brand')
                    ->label('Brands')
                    ->relationship('brand', 'name'),
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
            'index' => Pages\ListServerPlans::route('/'),
            'create' => Pages\CreateServerPlan::route('/create'),
            'view' => Pages\ViewServerPlan::route('/{record}'),
            'edit' => Pages\EditServerPlan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'category', 'brand'];
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 10 ? 'success':'danger';
    }
}
