<?php
namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\RelationManagers;
use App\Models\ClientTraffic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Carbon\Carbon;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Models\ServerClient;
use App\Models\Server;
use App\Services\XUIService;
use App\Services\CacheService;

class ClientTrafficResource extends Resource
{
    protected static ?string $model = ClientTraffic::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static UnitEnum|string|null $navigationGroup = 'TRAFFIC MONITORING';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getLabel(): string
    {
        return 'Client Traffic';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Create-only wizard
                Wizard::make()->label('Setup Client Traffic')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'w-full'])
                    ->visibleOn('create')
                    ->steps([
                        Step::make('Associate')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Link client & inbound')
                                    ->description('Pick the inbound and the customer. Email identifies the client traffic record.')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(2)->schema([
                                            Forms\Components\Select::make('server_inbound_id')->label('Server Inbound')->relationship('serverInbound', 'remark')->required()->searchable()->preload(),
                                            Forms\Components\Select::make('customer_id')->label('Customer')->relationship('customer', 'name')->required()->searchable()->preload(),
                                        ]),
                                        \Filament\Schemas\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('email')->label('Client Email')->required()->email()->maxLength(255),
                                            Forms\Components\Toggle::make('enable')->label('Enabled')->default(true),
                                        ]),
                                    ]),
                            ])->columns(1),
                        Step::make('Statistics')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Initialize counters (bytes)')
                                    ->description('You can leave these as 0; they will be updated by sync jobs')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('up')->label('Upload (Bytes)')->numeric()->default(0),
                                            Forms\Components\TextInput::make('down')->label('Download (Bytes)')->numeric()->default(0),
                                            Forms\Components\TextInput::make('total')->label('Total (Bytes)')->numeric()->default(0),
                                        ]),
                                    ]),
                            ])->columns(1),
                        Step::make('Expiry')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Record validity')
                                    ->description('Set when this traffic record should expire')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('expiry_time')->label('Expiry Date & Time')->required(),
                                    ]),
                            ])->columns(1),

                        Step::make('Review')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Section::make('Quick summary')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(3)->schema([
                                            Forms\Components\Placeholder::make('sum_email')->label('Email')->content(fn ($get) => (string) $get('email') ?: '—'),
                                            Forms\Components\Placeholder::make('sum_inbound')->label('Inbound')->content(fn ($get) => optional(\App\Models\ServerInbound::find($get('server_inbound_id')))?->remark ?: '—'),
                                            Forms\Components\Placeholder::make('sum_customer')->label('Customer')->content(fn ($get) => optional(\App\Models\Customer::find($get('customer_id')))?->name ?: '—'),
                                        ]),
                                    ]),
                            ])->columns(1),
                    ]),

                Group::make()->schema([
                    Section::make('📊 Usage Counters')
                        ->description('Adjust traffic counters (bytes) and set when this record expires.')
                        ->columns(2)
                        ->schema([
                            Group::make()->schema([
                        TextInput::make('up')
                            ->label('Upload (Bytes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Total upload traffic in bytes'),

                        TextInput::make('down')
                            ->label('Download (Bytes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Total download traffic in bytes'),

                        TextInput::make('total')
                            ->label('Total (Bytes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Total combined traffic in bytes'),

                        TextInput::make('reset')
                            ->label('Reset Count')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Number of times traffic was reset'),
                            ])->columnSpan(1),

                            Group::make()->schema([
                        DateTimePicker::make('expiry_time')
                            ->label('Expiry Date & Time')
                            ->required()
                            ->helperText('When this traffic record expires'),
                            ])->columnSpan(1),
                        ]),
                ])->columnSpanFull(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Client identifier'),

                BadgeColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->tooltip('Associated customer'),

                BadgeColumn::make('serverInbound.remark')
                    ->label('Inbound')
                    ->searchable()
                    ->sortable()
                    ->color('info')
                    ->tooltip('Server inbound configuration'),

                IconColumn::make('enable')
                    ->label('Enabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('traffic_usage')
                    ->label('Traffic Used')
                    ->getStateUsing(function ($record) {
                        $totalGB = ($record->up + $record->down) / 1024 / 1024 / 1024;
                        return number_format($totalGB, 2) . ' GB';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $totalGB = ($record->up + $record->down) / 1024 / 1024 / 1024;
                        if ($totalGB > 100) return 'danger';
                        if ($totalGB > 50) return 'warning';
                        if ($totalGB > 10) return 'info';
                        return 'success';
                    })
                    ->tooltip(function ($record) {
                        $upMB = number_format($record->up / 1024 / 1024, 2);
                        $downMB = number_format($record->down / 1024 / 1024, 2);
                        return "Upload: {$upMB} MB\nDownload: {$downMB} MB";
                    }),

                TextColumn::make('up')
                    ->label('Upload')
                    ->getStateUsing(fn ($record) => number_format($record->up / 1024 / 1024, 2) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('down')
                    ->label('Download')
                    ->getStateUsing(fn ($record) => number_format($record->down / 1024 / 1024, 2) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn ($record) => number_format($record->total / 1024 / 1024, 2) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reset')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->tooltip('Number of times traffic was reset'),

                TextColumn::make('expiry_time')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->expiry_time) return 'primary';

                        $expiry = Carbon::parse($record->expiry_time);
                        $now = Carbon::now();

                        if ($expiry->isPast()) return 'danger';
                        if ($expiry->diffInDays($now) <= 7) return 'warning';
                        if ($expiry->diffInDays($now) <= 30) return 'info';
                        return 'success';
                    })
                    ->tooltip(function ($record) {
                        if (!$record->expiry_time) return 'No expiry set';
                        return Carbon::parse($record->expiry_time)->diffForHumans();
                    }),

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
                SelectFilter::make('server_inbound_id')
                    ->relationship('serverInbound', 'remark')
                    ->label('Inbound')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('enable')
                    ->label('Enabled Status')
                    ->placeholder('All records')
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only'),

                Tables\Filters\Filter::make('expired')
                    ->toggle()
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('expiry_time', '<', now())),

                Tables\Filters\Filter::make('high_usage')
                    ->toggle()
                    ->label('High Usage (>10GB)')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(up + down) > ?', [10 * 1024 * 1024 * 1024])),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View traffic details'),

                    EditAction::make()
                        ->tooltip('Edit traffic record'),

                    Action::make('refresh_live')
                        ->label('Refresh Live')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->tooltip('Fetch live traffic from 3X-UI and cache it')
                        ->action(function ($record) {
                            // Try to locate matching ServerClient to resolve server context
                            $serverClient = ServerClient::query()
                                ->where('email', $record->email)
                                ->when($record->server_inbound_id, fn($q) => $q->where('server_inbound_id', $record->server_inbound_id))
                                ->first();

                            if (!$serverClient) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No matching client found')
                                    ->body('Unable to resolve server context for live refresh.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $server = $serverClient->server ?? $serverClient->inbound?->server;
                            if (!$server instanceof Server) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Server not found')
                                    ->body('Cannot determine server for this client.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $xui = new XUIService($server);
                            $cache = app(CacheService::class);

                            // Prefer UUID if present; fallback to email
                            $live = null;
                            if (!empty($serverClient->id)) {
                                $live = $xui->getClientByUuid((string)$serverClient->id);
                            }
                            if (!$live) {
                                $live = $xui->getClientByEmail($record->email);
                            }

                            if ($live) {
                                $cacheKeyUuid = $serverClient->uuid ?: (string) $serverClient->id;
                                if ($cacheKeyUuid) {
                                    $cache->cacheClientTraffic($cacheKeyUuid, [
                                        'up' => (int)($live['up'] ?? 0),
                                        'down' => (int)($live['down'] ?? 0),
                                        'total' => (int)($live['total'] ?? 0),
                                        'fetched_at' => now()->toISOString(),
                                    ]);
                                }

                                // Update local record as a convenience snapshot
                                $record->update([
                                    'up' => (int)($live['up'] ?? 0),
                                    'down' => (int)($live['down'] ?? 0),
                                    'total' => (int)($live['total'] ?? (($live['up'] ?? 0) + ($live['down'] ?? 0))),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Live traffic refreshed')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('No live data')
                                    ->body('3X-UI did not return traffic for this client.')
                                    ->warning()
                                    ->send();
                            }
                        }),

                    Action::make('reset_traffic')
                        ->label('Reset Traffic')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Traffic Statistics')
                        ->modalDescription('Are you sure you want to reset traffic statistics for this client?')
                        ->action(function ($record) {
                            // Attempt remote reset if we can resolve server/inbound context
                            $serverClient = ServerClient::query()->where('email', $record->email)->first();
                            $remoteResetOk = false;
                            try {
                                if ($serverClient && ($server = $serverClient->server ?? $serverClient->inbound?->server)) {
                                    $xui = new XUIService($server);
                                    // Derive inbound id: prefer remote_inbound_id on client, else serverInbound.remote_id
                                    $inboundId = $serverClient->remote_inbound_id
                                        ?: optional($serverClient->serverInbound)->remote_id
                                        ?: null;
                                    if ($inboundId) {
                                        $remoteResetOk = $xui->resetClientTraffic((int)$inboundId, $record->email);
                                    }
                                }
                            } catch (\Throwable $e) {
                                // Continue with local reset if remote fails
                            }

                            $record->update([
                                'up' => 0,
                                'down' => 0,
                                'total' => 0,
                                'reset' => $record->reset + 1,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title($remoteResetOk ? 'Traffic reset (remote + local)' : 'Traffic reset (local)')
                                ->success()
                                ->send();
                        })
                        ->tooltip('Reset traffic statistics'),

                    DeleteAction::make()
                        ->tooltip('Delete traffic record'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->tooltip('Delete selected records'),

                    BulkAction::make('reset_selected_traffic')
                        ->label('Reset Traffic for Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'up' => 0,
                                    'down' => 0,
                                    'total' => 0,
                                    'reset' => $record->reset + 1,
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk traffic reset completed')
                                ->body("Reset traffic for {$count} records.")
                                ->success()
                                ->send();
                        })
                        ->tooltip('Reset traffic for selected records'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // Auto-refresh every minute for real-time traffic monitoring

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-chart-pie',
                'heading' => 'No traffic records',
                'description' => 'Adjust filters or time window.',
            ],
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Traffic Details')
                ->persistTab()
                ->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-m-user-circle')
                        ->schema([
                            InfolistSection::make('Identity & Status')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])
                                ->schema([
                                    TextEntry::make('email')->label('Email')->copyable()->color('primary'),
                                    IconEntry::make('enable')->label('Enabled')->boolean(),
                                    TextEntry::make('customer.name')->label('Customer')->default('—')->badge()->color('secondary'),
                                    TextEntry::make('serverInbound.remark')->label('Inbound')->badge()->color('info')
                                        ->url(fn($record) => optional($record->serverInbound?->id) ? \App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource::getUrl('view', ['record' => $record->serverInbound->id]) : null)
                                        ->openUrlInNewTab(),
                                ]),
                        ]),

                    Tabs\Tab::make('Usage')
                        ->icon('heroicon-m-chart-bar')
                        ->schema([
                            InfolistSection::make('Traffic')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 3,
                                ])
                                ->schema([
                                    TextEntry::make('up')->label('Upload')
                                        ->formatStateUsing(fn($s) => number_format(($s ?? 0)/1024/1024/1024, 2) . ' GB')
                                        ->badge()->color('primary'),
                                    TextEntry::make('down')->label('Download')
                                        ->formatStateUsing(fn($s) => number_format(($s ?? 0)/1024/1024/1024, 2) . ' GB')
                                        ->badge()->color('primary'),
                                    TextEntry::make('total')->label('Total')
                                        ->formatStateUsing(fn($s) => number_format(($s ?? 0)/1024/1024/1024, 2) . ' GB')
                                        ->badge()->color('info'),
                                    TextEntry::make('reset')->label('Reset Count')->badge()->color('warning'),
                                ]),
                        ]),

                    Tabs\Tab::make('Meta')
                        ->icon('heroicon-m-clock')
                        ->schema([
                            InfolistSection::make('Timestamps')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    TextEntry::make('expiry_time')->label('Expires')->dateTime()->badge()->color('info'),
                                    TextEntry::make('created_at')->label('Created')->since(),
                                    TextEntry::make('updated_at')->label('Updated')->since(),
                                ]),
                        ]),
                ])
                ->contained(true)
                ->columnSpanFull(),
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
            'index' => Pages\ListClientTraffic::route('/'),
            'create' => Pages\CreateClientTraffic::route('/create'),
            'view' => Pages\ViewClientTraffic::route('/{record}'),
            'edit' => Pages\EditClientTraffic::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'customer.name', 'serverInbound.remark'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 50 ? 'success' : 'warning';
    }
}
