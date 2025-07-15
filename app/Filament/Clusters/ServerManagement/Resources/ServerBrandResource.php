<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\RelationManagers;
use App\Models\ServerBrand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Str;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Group;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Forms\Get;
use App\Services\XUIService;
use Filament\Notifications\Notification;

class ServerBrandResource extends Resource
{
    protected static ?string $model = ServerBrand::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?int $navigationSort = 7;

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationGroup = 'SERVER ORGANIZATION';

    public static function getLabel(): string
    {
        return 'Brands';
    }

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Section::make('Brand Information')->schema([
                    Grid::make()
                        ->schema([
                            Section::make('Basic Details')->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Set $set)
                                        {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                            }
                                        })
                                    ->helperText('Unique brand name for this server provider'),
                                TextInput::make('slug')
                                    ->required()
                                    ->disabled()
                                    ->unique(ServerBrand::class, 'slug', ignoreRecord: true)
                                    ->helperText('Auto-generated URL-friendly identifier'),
                                Forms\Components\TextInput::make('website_url')
                                    ->label('Website URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://example.com')
                                    ->helperText('Official website of the server provider'),
                                Forms\Components\TextInput::make('support_url')
                                    ->label('Support URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://support.example.com')
                                    ->helperText('Support page or contact URL'),
                                Forms\Components\Select::make('tier')
                                    ->label('Service Tier')
                                    ->options([
                                        'premium' => 'Premium',
                                        'standard' => 'Standard',
                                        'budget' => 'Budget',
                                        'enterprise' => 'Enterprise',
                                    ])
                                    ->default('standard')
                                    ->helperText('Quality tier for pricing and features'),
                            ])->columns(2),

                            Section::make('Branding')->schema([
                                FileUpload::make('image')
                                    ->label('Brand Logo')
                                    ->image()
                                    ->directory('server_brands')
                                    ->imageResizeMode('contain')
                                    ->imageCropAspectRatio('16:9')
                                    ->imageResizeTargetWidth('400')
                                    ->imageResizeTargetHeight('225')
                                    ->helperText('Brand logo (recommended: 400x225px)')
                                    ->columnSpanFull(),
                                Forms\Components\ColorPicker::make('brand_color')
                                    ->label('Brand Color')
                                    ->hex()
                                    ->helperText('Primary brand color for UI elements'),
                            ]),

                            Section::make('Configuration & Status')->schema([
                                Toggle::make('is_active')
                                    ->label('Active Brand')
                                    ->required()
                                    ->default(true)
                                    ->helperText('Enable/disable this brand for new servers'),
                                Toggle::make('featured')
                                    ->label('Featured Brand')
                                    ->default(false)
                                    ->helperText('Show as featured on customer-facing pages'),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Display order (lower numbers first)'),
                            ])->columns(3),
                        ]),

                    MarkdownEditor::make('desc')
                        ->label('Brand Description')
                        ->columnSpanFull()
                        ->fileAttachmentsDirectory('server_brands')
                        ->helperText('Detailed description of the server brand and its features'),
                ]),
            ]);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Logo')
                    ->circular()
                    ->size(50),
                Tables\Columns\TextColumn::make('name')
                    ->label('Brand Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy'),
                Tables\Columns\BadgeColumn::make('tier')
                    ->label('Tier')
                    ->colors([
                        'success' => 'premium',
                        'primary' => 'standard',
                        'warning' => 'budget',
                        'secondary' => 'enterprise',
                    ]),
                Tables\Columns\TextColumn::make('servers_count')
                    ->label('Servers')
                    ->counts('servers')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')
                    ->url(fn($record) => $record->website_url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options([
                        'premium' => 'Premium',
                        'standard' => 'Standard',
                        'budget' => 'Budget',
                        'enterprise' => 'Enterprise',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('featured')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('featured', true)),
                Tables\Filters\Filter::make('is_active')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('test_xui_connections')
                        ->label('Test X-UI Connections')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->action(function ($record) {
                            $servers = $record->servers()->whereNotNull('api_url')->get();
                            $results = [];

                            foreach ($servers as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    $connected = $xuiService->login();
                                    $results[] = [
                                        'server' => $server->name,
                                        'status' => $connected ? 'success' : 'failed',
                                        'message' => $connected ? 'Connected successfully' : 'Connection failed'
                                    ];
                                } catch (\Exception $e) {
                                    $results[] = [
                                        'server' => $server->name,
                                        'status' => 'error',
                                        'message' => $e->getMessage()
                                    ];
                                }
                            }

                            $successCount = collect($results)->where('status', 'success')->count();
                            $totalCount = count($results);

                            if ($successCount === $totalCount && $totalCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('X-UI Connection Test')
                                    ->body("All {$totalCount} servers connected successfully!")
                                    ->send();
                            } elseif ($successCount > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('X-UI Connection Test')
                                    ->body("{$successCount}/{$totalCount} servers connected successfully")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('X-UI Connection Test')
                                    ->body($totalCount > 0 ? "All {$totalCount} connection attempts failed" : "No servers with X-UI API configured")
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Test X-UI Connections')
                        ->modalDescription('This will test X-UI API connectivity for all servers under this brand.')
                        ->modalSubmitActionLabel('Test Connections'),
                    Tables\Actions\Action::make('view_servers')
                        ->label('View Servers')
                        ->icon('heroicon-o-server')
                        ->url(fn($record) => route('filament.admin.clusters.server-management.resources.servers.index', ['tableFilters[server_brand_id][values][]' => $record->id]))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_featured')
                        ->label('Toggle Featured')
                        ->icon('heroicon-o-star')
                        ->action(fn(Collection $records) => $records->each(fn($record) => $record->update(['featured' => ! $record->featured]))),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each(fn($record) => $record->update(['is_active' => true]))),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn(Collection $records) => $records->each(fn($record) => $record->update(['is_active' => false]))),
                ]),
            ])
            ->defaultSort('sort_order');
        }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServersRelationManager::class,
        ];
    }

    public static function getPages(): array
        {
        return [
            'index' => Pages\ListServerBrands::route('/'),
            'create' => Pages\CreateServerBrand::route('/create'),
            'view' => Pages\ViewServerBrand::route('/{record}'),
            'edit' => Pages\EditServerBrand::route('/{record}/edit'),

        ];
        }
    }
