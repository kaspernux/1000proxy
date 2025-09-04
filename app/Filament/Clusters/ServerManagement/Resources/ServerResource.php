<?php
namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Grid;
use App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;
use App\Filament\Clusters\ServerManagement;
use App\Filament\Concerns\HasPerformanceOptimizations;
use App\Models\Server;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Services\XUIService;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource as InboundResource;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource as ClientResource;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Schemas\Schema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;


class ServerResource extends Resource
{
    use HasPerformanceOptimizations;
    protected static ?string $model = Server::class;

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        // Admin/manager manage; support_manager can view per policy, sales_support no.
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?int $navigationSort = 1;
    protected static array $searchableAttributes = ['name', 'host', 'ip_address', 'country', 'description'];


    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Servers';

    protected static ?string $pluralModelLabel = 'Servers';

    protected static ?string $modelLabel = 'Server';

    public static function getLabel(): string
    {
        return 'XUI Servers';
    }

    public static function form(Schema $schema): Schema
    {
    return $schema->schema([
            // Guided wizard for both create and edit contexts
            Wizard::make()->label('Server Setup & Edit')
                ->columnSpanFull()
                ->extraAttributes(['class' => 'w-full'])
                ->steps([
                    Step::make('Basics')
                        ->icon('heroicon-o-server')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('My EU Proxy 01')
                                    ->helperText('A friendly name for this server'),
                                Forms\Components\Select::make('server_category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('General'),
                            ]),
                            Grid::make(2)->schema([
                                Forms\Components\Select::make('server_brand_id')
                                    ->label('Brand')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select provider'),
                                // Legacy 'location' alias used by tests — mirror into 'country'
                                TextInput::make('location')
                                    ->label('Location')
                                    ->maxLength(255)
                                    ->placeholder('Germany')
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, $set) => $set('country', $state)),
                                TextInput::make('country')
                                    ->label('Country')
                                    ->maxLength(255)
                                    ->placeholder('Germany'),
                            ]),
                            Textarea::make('description')
                                ->rows(3)
                                ->placeholder('Short description or notes (optional)'),
                        ])->columns(1),
                    Step::make('Connection')
                        ->icon('heroicon-o-link')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('panel_url')
                                    ->label('Panel URL')
                                    ->url()
                                    ->placeholder('https://panel.example.com')
                                    ->helperText('If set, host/port may be auto-derived'),
                                TextInput::make('host')
                                    ->label('Host/Hostname')
                                    ->maxLength(255)
                                    ->placeholder('panel.example.com'),
                                TextInput::make('panel_port')
                                    ->label('Panel Port')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->default(2053)
                                    ->placeholder('2053'),
                            ]),
                            Grid::make(3)->schema([
                                TextInput::make('ip_address')
                                    ->label('IP Address')
                                    ->required()
                                    ->rules(['ip'])
                                    ->afterStateUpdated(fn($state, callable $set) => $set('ip', $state))
                                    ->default(request()->server('SERVER_ADDR') ?? gethostbyname(gethostname()))
                                    ->placeholder(request()->server('SERVER_ADDR') ?? gethostbyname(gethostname())),
                                TextInput::make('web_base_path')
                                    ->label('Web Base Path')
                                    ->default('/')
                                    ->placeholder('/')
                                    ->helperText('e.g. / or /proxy')
                                    ->dehydrateStateUsing(function($state){
                                        if (!$state) return '/';
                                        $state = '/' . trim($state, '/');
                                        return $state === '//' ? '/' : $state;
                                    }),
                                Forms\Components\Select::make('port_type')
                                    ->label('Port Type')
                                    ->options([
                                        'https' => 'HTTPS',
                                        'http' => 'HTTP',
                                        'tcp' => 'TCP',
                                        'udp' => 'UDP',
                                    ]),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('username')
                                    ->label('Panel Username')
                                    ->required()
                                    ->placeholder('admin'),
                                TextInput::make('password')
                                    ->label('Panel Password')
                                    ->password()
                                    ->required()
                                    ->placeholder('••••••••'),
                                // Legacy aliases: allow tests to supply panel_username/panel_password
                                TextInput::make('panel_username')
                                    ->label('Panel Username (alias)')
                                    ->reactive()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(fn ($state, $set) => $set('username', $state))
                                    ->afterStateUpdated(fn ($state, $set) => $set('username', $state))
                                    ->placeholder('admin')
                                    ->helperText('Legacy alias for automated tests.'),
                                TextInput::make('panel_password')
                                    ->label('Panel Password (alias)')
                                    ->reactive()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(fn ($state, $set) => $set('password', $state))
                                    ->afterStateUpdated(fn ($state, $set) => $set('password', $state))
                                    ->placeholder('••••••••')
                                    ->helperText('Legacy alias for automated tests.'),
                            ]),
                        ])->columns(1),
                    Step::make('Security & Protocol')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Grid::make(3)->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Panel Type')
                                    ->options([
                                        'sanaei' => '3X-UI (Sanaei)',
                                        'alireza' => 'Alireza',
                                        'marzban' => 'Marzban',
                                        'other' => 'Other',
                                    ])
                                    ->default('sanaei')
                                    ->required(),
                                Forms\Components\Select::make('security')
                                    ->label('Security')
                                    ->options([
                                        'tls' => 'TLS',
                                        'reality' => 'Reality',
                                        'none' => 'None',
                                    ])
                                    ->default('tls'),
                                Forms\Components\Select::make('header_type')
                                    ->label('Header Type')
                                    ->options([
                                        'none' => 'None',
                                        'http' => 'HTTP',
                                        'ws' => 'WebSocket',
                                        'grpc' => 'gRPC',
                                    ])
                                    ->default('none'),
                            ]),
                            Grid::make(3)->schema([
                                TextInput::make('sni')->label('SNI')->placeholder('example.com'),
                                TextInput::make('port')->label('Main Port')->numeric()->minValue(1)->maxValue(65535)->placeholder('443'),
                                TextInput::make('flag')->label('Flag (ISO code)')->maxLength(10)->placeholder('DE'),
                            ]),
                        ])->columns(1),
                    Step::make('Automation')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->schema([
                            Grid::make(3)->schema([
                                Toggle::make('auto_sync_enabled')->label('Auto Sync')->default(true),
                                Toggle::make('auto_provisioning')->label('Auto Provisioning')->default(false),
                                TextInput::make('sync_interval_minutes')->label('Sync Interval (min)')->numeric()->minValue(1)->maxValue(1440)->default(30),
                            ]),
                        ])->columns(1)
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('name')
                    ->label('🏷️ Server Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-server')
                    ->copyable()
                    ->copyMessage('Server name copied')
                    ->copyMessageDuration(1500)
                    ->weight('bold'),

                TextColumn::make('country')
                    ->label('🌍 Location')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin')
                    ->formatStateUsing(fn (string $state, Server $record): string =>
                        $record->flag ? "{$record->flag} {$state}" : $state)
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label('📊 Status')
                    ->colors([
                        'success' => ['healthy', 'up'],
                        'danger' => ['unhealthy', 'down', 'offline'],
                        'warning' => 'warning',
                        'secondary' => ['maintenance', 'paused'],
                    ])
                    ->icons([
                        'heroicon-o-heart' => ['healthy', 'up'],
                        'heroicon-o-x-circle' => ['unhealthy', 'down', 'offline'],
                        'heroicon-o-exclamation-triangle' => 'warning',
                        'heroicon-o-pause-circle' => 'paused',
                        'heroicon-o-wrench-screwdriver' => 'maintenance',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'up' => '🟢 Up',
                        'down' => '🔴 Down',
                        'paused' => '⏸️ Paused',
                        'healthy' => '🟢 Healthy',
                        'warning' => '🟡 Warning',
                        'unhealthy' => '🔴 Unhealthy',
                        'offline' => '⚫ Offline',
                        'maintenance' => '🔧 Maintenance',
                        default => ucfirst($state)
                    }),

                TextColumn::make('category.name')
                    ->label('🏷️ Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('brand.name')
                    ->label('🏢 Brand')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                BadgeColumn::make('xui_session_status')
                    ->label('🔐 XUI Session')
                    ->getStateUsing(fn (Server $record): string =>
                        $record->session_expires_at && $record->session_expires_at > now()
                            ? 'active'
                            : 'expired'
                    )
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-x-circle' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => '🟢 Active',
                        'expired' => '🔴 Expired',
                        default => '❓ Unknown'
                    })
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('⚙️ Panel Type')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'sanaei' => '⚡ 3X-UI Sanaei',
                        'alireza' => '🔧 Alireza Panel',
                        'marzban' => '🏗️ Marzban Panel',
                        default => ucfirst($state)
                    }),

                TextColumn::make('host')
                    ->label('🌐 Host/IP')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Host copied')
                    ->fontFamily('mono')
                    ->toggleable(),

                TextColumn::make('active_clients')
                    ->label('👥 Active')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->alignCenter(),

                BadgeColumn::make('response_time_ms')
                    ->label('⏱️ Resp (ms)')
                    ->numeric()
                    ->colors([
                        'success' => fn ($state) => $state !== null && $state < 300,
                        'warning' => fn ($state) => $state !== null && $state >= 300 && $state < 800,
                        'danger' => fn ($state) => $state !== null && $state >= 800,
                    ])
                    ->icon('heroicon-o-bolt')
                    ->alignCenter()
                    ->toggleable(),

                BadgeColumn::make('uptime_percentage')
                    ->label('📈 Uptime %')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float)$state, 2) : '—')
                    ->colors([
                        'danger' => fn ($state) => $state !== null && $state < 95,
                        'warning' => fn ($state) => $state !== null && $state >= 95 && $state < 99.5,
                        'success' => fn ($state) => $state !== null && $state >= 99.5,
                    ])
                    ->icon('heroicon-o-chart-bar')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('total_clients')
                    ->label('📊 Total')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-user-group')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_traffic_mb')
                    ->label('📈 Traffic (MB)')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? number_format((float) $state, 2) : '0')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_inbounds')
                    ->label('📥 Inbounds')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-inbox-stack')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                IconColumn::make('auto_provisioning')
                    ->label('🚀 Auto Provision')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('auto_sync_enabled')
                    ->label('🔄 Auto Sync')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_health_check_at')
                    ->label('💓 Last Health Check')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->icon('heroicon-o-heart')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_connected_at')
                    ->label('🔗 Last Connection')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->icon('heroicon-o-link')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('📊 Status Filter')
                    ->options([
                        'up' => '🟢 Up',
                        'down' => '🔴 Down',
                        'paused' => '⏸️ Paused',
                        // legacy labels for backward compatibility
                        'healthy' => '🟢 Healthy',
                        'warning' => '🟡 Warning',
                        'unhealthy' => '🔴 Unhealthy',
                        'offline' => '⚫ Offline',
                        'maintenance' => '🔧 Maintenance',
                    ])
                    ->multiple(),

                SelectFilter::make('server_category_id')
                    ->label('🏷️ Category Filter')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('server_brand_id')
                    ->label('🏢 Brand Filter')
                    ->relationship('brand', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('⚙️ Panel Type Filter')
                    ->options([
                        'sanaei' => '⚡ 3X-UI Sanaei',
                        'alireza' => '🔧 Alireza Panel',
                        'marzban' => '🏗️ Marzban Panel',
                        'other' => '🔗 Other',
                    ])
                    ->multiple(),

                SelectFilter::make('country')
                    ->label('🌍 Country Filter')
                    ->options(function () {
                        return Server::distinct()
                            ->pluck('country', 'country')
                            ->filter()
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable(),

                Filter::make('auto_provisioning')
                    ->label('🚀 Auto Provisioning Enabled')
                    ->query(fn (Builder $query): Builder => $query->where('auto_provisioning', true)),

                Filter::make('auto_sync_enabled')
                    ->label('🔄 Auto Sync Enabled')
                    ->query(fn (Builder $query): Builder => $query->where('auto_sync_enabled', true)),

                Filter::make('has_active_clients')
                    ->label('👥 Has Active Clients')
                    ->query(fn (Builder $query): Builder => $query->where('active_clients', '>', 0)),

                Filter::make('recent_health_check')
                    ->label('💓 Recent Health Check (Last 24h)')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('last_health_check_at', '>=', now()->subDay())),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('login_and_sync'),
                    Action::make('test_connection'),
                    Action::make('sync_data'),
                    Action::make('reset_session'),
                    Action::make('view_inbounds'),
                    Action::make('view_clients'),
                    Action::make('online_clients'),
                    ViewAction::make()->color('info'),
                    EditAction::make()->color('warning'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    \Filament\Actions\BulkAction::make('bulk_test_connection')
                        ->label('🔗 Test Connections')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $successful = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $result = $xuiService->testConnection();

                                    if ($result) {
                                        $record->update([
                                            'last_connected_at' => now(),
                                            'status' => 'up',
                                            'health_status' => 'healthy',
                                        ]);
                                        $successful++;
                                    } else {
                                        $record->update([
                                            'status' => 'down',
                                            'health_status' => 'unhealthy',
                                        ]);
                                        $failed++;
                                    }
                                } catch (\Exception $e) {
                                    $record->update([
                                        'status' => 'down',
                                        'health_status' => 'error',
                                        'health_message' => $e->getMessage(),
                                    ]);
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('🔗 Bulk Connection Test Complete')
                                ->body("✅ Successful: {$successful}, ❌ Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_sync_data')
                        ->label('🔄 Sync All Data')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $successful = 0;
                            $failed = 0;
                            $totalInbounds = 0;
                            $totalClients = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $inboundCount = $xuiService->syncAllInbounds();
                                    $clientCount = $xuiService->syncAllClients();

                                    $totalInbounds += $inboundCount;
                                    $totalClients += $clientCount;

                                    $record->update(['last_global_sync_at' => now()]);
                                    $successful++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('🔄 Bulk Sync Complete')
                                ->body("✅ Successful: {$successful}, ❌ Failed: {$failed} | Synced: {$totalInbounds} inbounds, {$totalClients} clients")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_login_and_sync')
                        ->label('🔐 Login & Sync')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $ok = 0; $fail = 0; $inboundsTotal = 0; $clientsTotal = 0;
                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    if ($xuiService->testConnection()) {
                                        $inboundsTotal += $xuiService->syncAllInbounds();
                                        $clientsTotal += $xuiService->syncAllClients();
                                        $ok++;
                                    } else {
                                        $fail++;
                                    }
                                } catch (\Exception $e) {
                                    $fail++;
                                }
                            }
                            Notification::make()
                                ->title('🔐 Bulk Login & Sync Complete')
                                ->body("✅ {$ok} succeeded / ❌ {$fail} failed | Inbounds: {$inboundsTotal} Clients: {$clientsTotal}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_enable_auto_provisioning')
                        ->label('🚀 Enable Auto Provisioning')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['auto_provisioning' => true]);
                            }

                            Notification::make()
                                ->title('🚀 Auto Provisioning Enabled')
                                ->body("Enabled auto provisioning for {$count} servers")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_enable_auto_sync')
                        ->label('🔄 Enable Auto Sync')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['auto_sync_enabled' => true]);
                            }

                            Notification::make()
                                ->title('🔄 Auto Sync Enabled')
                                ->body("Enabled auto sync for {$count} servers")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_reset_sessions')
                        ->label('🔐 Reset XUI Sessions')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset XUI Sessions')
                        ->modalDescription('This will clear all stored session cookies and force re-authentication on next API call.')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update([
                                    'session_cookie' => null,
                                    'session_expires_at' => null,
                                    'login_attempts' => 0,
                                ]);
                            }

                            Notification::make()
                                ->title('🔐 Sessions Reset')
                                ->body("Reset XUI sessions for {$count} servers")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_health_check')
                        ->label('💓 Health Check')
                        ->icon('heroicon-o-heart')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $healthy = 0;
                            $unhealthy = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $isHealthy = $xuiService->testConnection();

                                    if ($isHealthy) {
                                        $record->update([
                                            'status' => 'up',
                                            'last_health_check_at' => now(),
                                            'health_status' => 'healthy',
                                            'health_message' => 'Server is responding normally'
                                        ]);
                                        $healthy++;
                                    } else {
                                        $record->update([
                                            'status' => 'down',
                                            'last_health_check_at' => now(),
                                            'health_status' => 'unhealthy',
                                            'health_message' => 'Server not responding'
                                        ]);
                                        $unhealthy++;
                                    }
                                } catch (\Exception $e) {
                                    $record->update([
                                        'status' => 'down',
                                        'last_health_check_at' => now(),
                                        'health_status' => 'error',
                                        'health_message' => $e->getMessage()
                                    ]);
                                    $unhealthy++;
                                }
                            }

                            Notification::make()
                                ->title('💓 Health Check Complete')
                                ->body("✅ Healthy: {$healthy}, ❌ Unhealthy: {$unhealthy}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_backup_create')
                        ->label('💾 Create Backups')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->color('secondary')
                        ->action(function (Collection $records) {
                            $successful = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    $result = $xuiService->createBackup();

                                    if ($result) {
                                        $successful++;
                                    } else {
                                        $failed++;
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('💾 Backup Creation Complete')
                                ->body("✅ Successful: {$successful}, ❌ Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('bulk_cleanup_depleted')
                        ->label('🧹 Cleanup Depleted Clients')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cleanup Depleted Clients')
                        ->modalDescription('This will remove all clients that have exceeded their traffic limits. This action cannot be undone.')
                        ->action(function (Collection $records) {
                            $totalCleaned = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $xuiService = new XUIService($record);
                                    // Get all inbounds for this server and clean each one
                                    $inbounds = $xuiService->listInbounds();

                                    foreach ($inbounds as $inbound) {
                                        if (isset($inbound['id'])) {
                                            $cleaned = $xuiService->deleteDepletedClients($inbound['id']);
                                            if ($cleaned) {
                                                $totalCleaned++;
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('🧹 Cleanup Complete')
                                ->body("✅ Cleaned {$totalCleaned} inbounds, ❌ Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('reset_all_traffics')
                        ->label('📊 Reset Client Traffics')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset All Client Traffics')
                        ->modalDescription('This will reset traffic statistics for all clients on the selected servers.')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    // Get all inbounds and reset client traffics
                                    $inbounds = $xuiService->listInbounds();

                                    foreach ($inbounds as $inbound) {
                                        if (isset($inbound['id']) && isset($inbound['clientStats'])) {
                                            foreach ($inbound['clientStats'] as $client) {
                                                $xuiService->resetClientTraffic($inbound['id'], $client['email']);
                                            }
                                        }
                                    }
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failCount++;
                                    Log::error("Failed to reset traffics for server {$server->name}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('📊 Traffic Reset Complete')
                                ->body("Reset traffics: {$successCount} succeeded, {$failCount} failed")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('create_backups')
                        ->label('📦 Create Backups')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Create Backups')
                        ->modalDescription('This will create backups on the selected servers.')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    $xuiService->createBackup();
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failCount++;
                                    Log::error("Failed to create backup for server {$server->name}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('📦 Backup Creation Complete')
                                ->body("Created backups: {$successCount} succeeded, {$failCount} failed")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('update_server_status')
                        ->label('🔄 Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    $isOnline = $xuiService->testConnection();

                                    $server->update([
                                        'status' => $isOnline ? 'up' : 'down',
                                        'last_health_check_at' => now(),
                                    ]);

                                    $successCount++;
                                } catch (\Exception $e) {
                                    $server->update([
                                        'status' => 'down',
                                        'last_health_check_at' => now(),
                                    ]);
                                    $failCount++;
                                }
                            }

                            Notification::make()
                                ->title('🔄 Status Update Complete')
                                ->body("Updated status: {$successCount} succeeded, {$failCount} failed")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds

        return self::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-server-stack',
                'heading' => 'No servers found',
                'description' => 'Provision a new server or adjust your filters to see results.',
            ],
        ]);
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

    public static function getRelations(): array
    {
        return [
            // Relation managers can be added here when they exist
        ];
    }

    // View page infolist using Filament v4 Schemas & Infolists
    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Server Details')
                ->persistTab()
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Overview')
                        ->icon('heroicon-m-server')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Summary')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('name')
                                        ->label('Server Name')
                                        ->icon('heroicon-o-server')
                                        ->weight('bold')
                                        ->color('primary'),

                                    \Filament\Infolists\Components\TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->icon(fn ($state) => match ($state) {
                                            'up' => 'heroicon-o-heart',
                                            'down' => 'heroicon-o-x-circle',
                                            'paused' => 'heroicon-o-pause-circle',
                                            default => 'heroicon-o-question-mark-circle',
                                        })
                                        ->color(fn ($state) => match ($state) {
                                            'up' => 'success',
                                            'down' => 'danger',
                                            'paused' => 'secondary',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'up' => 'Up', 'down' => 'Down', 'paused' => 'Paused', default => ucfirst((string) $state)
                                        }),

                                    \Filament\Infolists\Components\TextEntry::make('health_status')
                                        ->label('Health')
                                        ->badge()
                                        ->icon('heroicon-o-heart')
                                        ->color(fn ($state) => match ($state) {
                                            'healthy' => 'success',
                                            'warning' => 'warning',
                                            'unhealthy' => 'danger',
                                            default => 'gray',
                                        }),

                                    \Filament\Infolists\Components\TextEntry::make('country')
                                        ->label('Location')
                                        ->icon('heroicon-o-map-pin')
                                        ->formatStateUsing(fn ($state, $record) => $record?->flag ? ($record->flag . ' ' . $state) : $state)
                                        ->badge()
                                        ->color('info'),

                                    \Filament\Infolists\Components\TextEntry::make('type')
                                        ->label('Panel Type')
                                        ->badge()
                                        ->color('warning')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'sanaei' => '3X-UI Sanaei',
                                            'alireza' => 'Alireza',
                                            'marzban' => 'Marzban',
                                            default => ucfirst((string) $state)
                                        }),

                                    \Filament\Infolists\Components\TextEntry::make('last_health_check_at')
                                        ->label('Last Health Check')
                                        ->since()
                                        ->icon('heroicon-o-heart'),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Connectivity')
                        ->icon('heroicon-m-link')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Connection & Access')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('host')->label('Host')->icon('heroicon-o-globe-alt')->copyable(),
                                    \Filament\Infolists\Components\TextEntry::make('ip')->label('IP')->icon('heroicon-o-computer-desktop')->copyable(),
                                    \Filament\Infolists\Components\TextEntry::make('port')->label('Port')->icon('heroicon-o-bolt'),
                                    \Filament\Infolists\Components\TextEntry::make('panel_url')->label('Panel URL')->icon('heroicon-o-link')->url(fn ($state) => $state, true)->copyable(),
                                    \Filament\Infolists\Components\TextEntry::make('web_base_path')->label('Web Base Path')->icon('heroicon-o-folder'),
                                    \Filament\Infolists\Components\TextEntry::make('security')->label('Security')->icon('heroicon-o-shield-check')->badge(),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Session')
                        ->icon('heroicon-m-key')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('X-UI Session')
                                ->columns(3)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('session_cookie_name')->label('Cookie Name')->icon('heroicon-o-key'),
                                    \Filament\Infolists\Components\TextEntry::make('session_expires_at')->label('Expires')->dateTime()->since()->icon('heroicon-o-clock'),
                                    \Filament\Infolists\Components\TextEntry::make('last_login_at')->label('Last Login')->dateTime()->since()->icon('heroicon-o-arrow-right-on-rectangle'),
                                    \Filament\Infolists\Components\TextEntry::make('login_attempts')->label('Login Attempts')->icon('heroicon-o-exclamation-triangle')->badge()->color('warning'),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Performance')
                        ->icon('heroicon-m-chart-bar')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Metrics')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('response_time_ms')->label('Response (ms)')->badge()->color(fn ($state) => $state !== null && $state < 300 ? 'success' : ($state !== null && $state < 800 ? 'warning' : 'danger'))->icon('heroicon-o-bolt'),
                                    \Filament\Infolists\Components\TextEntry::make('uptime_percentage')->label('Uptime %')->formatStateUsing(fn ($s) => $s !== null ? number_format((float)$s, 2) : '—')->badge()->color(fn ($s) => $s !== null && $s >= 99.5 ? 'success' : ($s !== null && $s >= 95 ? 'warning' : 'danger'))->icon('heroicon-o-chart-bar'),
                                    \Filament\Infolists\Components\TextEntry::make('total_inbounds')->label('Inbounds')->badge()->color('info')->icon('heroicon-o-inbox-stack'),
                                    \Filament\Infolists\Components\TextEntry::make('active_clients')->label('Active Clients')->badge()->color('success')->icon('heroicon-o-users'),
                                    \Filament\Infolists\Components\TextEntry::make('total_online_clients')->label('Online Now')->badge()->color('success')->icon('heroicon-o-signal'),
                                    \Filament\Infolists\Components\TextEntry::make('total_traffic_mb')->label('Total Traffic (MB)')->formatStateUsing(fn ($s) => number_format((float)($s ?? 0), 2))->badge()->color('secondary')->icon('heroicon-o-arrow-trending-up'),
                                ]),

                            \Filament\Schemas\Components\Section::make('Latest Health')
                                ->columns(1)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('health_message')->label('Message')->icon('heroicon-o-information-circle')->default('—'),
                                ]),
                        ]),
                ])
                ->contained(true)
                ->columnSpanFull(),
        ]);
    }
}
