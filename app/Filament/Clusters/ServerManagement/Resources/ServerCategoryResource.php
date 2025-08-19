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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\RelationManagers;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;


class ServerCategoryResource extends Resource
{
    protected static ?string $model = ServerCategory::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'SERVER ORGANIZATION';

    public static function getLabel(): string
    {
        return 'Categories';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()->schema([
                    Section::make('📂 Category Information')->schema([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->columnSpan(1)
                            ->helperText('Enter a descriptive name for this category'),

                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->disabled()
                            ->unique(ServerCategory::class, 'slug', ignoreRecord: true)
                            ->columnSpan(1)
                            ->helperText('Auto-generated from category name'),
                    ])->columns(2),

                    Section::make('🖼️ Visual & Media')->schema([
                        FileUpload::make('image')
                            ->label('Category Image')
                            ->image()
                            ->disk('public')
                            ->directory('server-categories')
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('450')
                            ->columnSpanFull()
                            ->helperText('Upload a representative image for this category (recommended: 800x450px)'),
                    ]),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('⚙️ Settings & Status')->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->required()
                            ->default(true)
                            ->helperText('Enable/disable this category'),
                    ]),

                    Section::make('📊 Statistics')->schema([
                        Forms\Components\Placeholder::make('servers_count')
                            ->label('Servers in Category')
                            ->content(fn ($record) => $record ? $record->servers()->count() : '0'),

                        Forms\Components\Placeholder::make('plans_count')
                            ->label('Plans in Category')
                            ->content(fn ($record) => $record ? $record->plans()->count() : '0'),
                    ])->hidden(fn ($context) => $context === 'create'),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->height(60)
                    ->defaultImageUrl(url('/images/placeholder-category.png'))
                    ->tooltip('Category image'),

                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->tooltip('Category name'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('URL slug for this category'),

                TextColumn::make('servers_count')
                    ->label('Servers')
                    ->getStateUsing(fn ($record) => $record->servers()->count())
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->tooltip('Number of servers in this category'),

                TextColumn::make('plans_count')
                    ->label('Plans')
                    ->getStateUsing(fn ($record) => $record->plans()->count())
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->tooltip('Number of plans in this category'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip('Active status'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All categories')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('has_servers')
                    ->toggle()
                    ->label('Has Servers')
                    ->query(fn (Builder $query): Builder => $query->whereHas('servers')),

                Tables\Filters\Filter::make('has_plans')
                    ->toggle()
                    ->label('Has Plans')
                    ->query(fn (Builder $query): Builder => $query->whereHas('plans')),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View category details'),

                    EditAction::make()
                        ->tooltip('Edit category'),

                    Action::make('view_servers')
                        ->label('View Servers')
                        ->icon('heroicon-o-server')
                        ->color('primary')
                        ->url(fn ($record) => route('filament.admin.server-management.resources.servers.index', [
                            'tableFilters[server_category_id][value]' => $record->id
                        ]))
                        ->tooltip('View servers in this category'),

                    Action::make('view_plans')
                        ->label('View Plans')
                        ->icon('heroicon-o-fire')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.server-management.resources.server-plans.index', [
                            'tableFilters[server_category_id][value]' => $record->id
                        ]))
                        ->tooltip('View plans in this category'),

                    DeleteAction::make()
                        ->tooltip('Delete category'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->tooltip('Delete selected categories'),

                    \Filament\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);

                            \Filament\Notifications\Notification::make()
                                ->title('Categories activated')
                                ->body('Selected categories have been activated.')
                                ->success()
                                ->send();
                        })
                        ->tooltip('Activate selected categories'),

                    \Filament\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);

                            \Filament\Notifications\Notification::make()
                                ->title('Categories deactivated')
                                ->body('Selected categories have been deactivated.')
                                ->warning()
                                ->send();
                        })
                        ->tooltip('Deactivate selected categories'),
                ]),
            ])
            ->defaultSort('name');

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-squares-plus',
                'heading' => 'No categories found',
                'description' => 'Create a category or adjust filters.',
            ],
        ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 5 ? 'success' : 'warning';
    }
}
