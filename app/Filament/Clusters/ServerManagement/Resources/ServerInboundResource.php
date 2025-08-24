<?php
namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServerInbound;
use Filament\Resources\Resource;
use App\Services\XUIService;
use Illuminate\Support\Facades\Log;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Redirect;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use App\Livewire\Traits\LivewireAlertV4;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;
use Filament\Actions\Action;
use App\Models\Server;
use Filament\Tables\Filters\Filter;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ServerInboundResource extends Resource
{
    use LivewireAlertV4;

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static ?string $model = ServerInbound::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationLabel = 'Server Inbounds';

    protected static ?string $pluralModelLabel = 'Server Inbounds';

    protected static UnitEnum|string|null $navigationGroup = 'XUI MANAGEMENT';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'server_id';

    public static function getLabel(): string
    {
        return 'Inbounds';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Create-only wizard
                Wizard::make()->label('Setup Inbound')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'w-full'])
                    ->visibleOn('create')
                    ->steps([
                        Step::make('Basics')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('server_id')
                                        ->relationship('server', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Select the server this inbound belongs to'),

                                    Forms\Components\TextInput::make('remark')
                                        ->label('Inbound Name/Remark')
                                        ->required()
                                        ->maxLength(255),
                                ]),

                                \Filament\Schemas\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('protocol')
                                        ->label('Protocol')
                                        ->required()
                                        ->options([
                                            'vless' => 'VLESS',
                                            'vmess' => 'VMESS',
                                            'trojan' => 'TROJAN',
                                            'shadowsocks' => 'Shadowsocks',
                                            'socks' => 'SOCKS',
                                            'http' => 'HTTP',
                                        ]),

                                    Forms\Components\Select::make('status')
                                        ->label('Local Status')
                                        ->options([
                                            'active' => '🟢 Active',
                                            'inactive' => '⭕ Inactive',
                                            'error' => '🔴 Error',
                                            'full' => '🟡 Full Capacity',
                                            'maintenance' => '🔧 Maintenance',
                                        ])
                                        ->default('active'),
                                ]),

                                \Filament\Schemas\Components\Grid::make(2)->schema([
                                    Forms\Components\Toggle::make('enable')->label('Enabled')->default(true),
                                    Forms\Components\Toggle::make('is_default')->label('Default Inbound')->default(false),
                                ]),
                            ])->columns(1),

                        Step::make('Network')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('listen')
                                        ->label('Listen IP')
                                        ->placeholder('0.0.0.0')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('port')
                                        ->label('Port')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(65535),
                                ]),
                                Forms\Components\TextInput::make('tag')
                                    ->label('Inbound Tag')
                                    ->maxLength(255)
                                    ->helperText('Optional identifier used by XUI'),
                            ])->columns(1),

                        Step::make('Provisioning')
                            ->icon('heroicon-o-cog-8-tooth')
                            ->schema([
                                Grid::make(3)->schema([
                                    Forms\Components\Toggle::make('provisioning_enabled')->label('Auto-Provisioning')->default(true),
                                    Forms\Components\TextInput::make('capacity')->label('Max Clients')->numeric()->minValue(0)->placeholder('Unlimited'),
                                    Forms\Components\TextInput::make('current_clients')->label('Current Clients')->numeric()->default(0)->disabled(),
                                ]),
                            ])->columns(1),
                    ]),

                Group::make()->schema([
                    Section::make('🏷️ Core Inbound Configuration')->schema([
                        Select::make('server_id')
                            ->relationship('server', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2)
                            ->helperText('Select the server this inbound belongs to'),

                        TextInput::make('remote_id')
                            ->label('Remote 3X-UI ID')
                            ->numeric()
                            ->placeholder('Auto-assigned by 3X-UI')
                            ->disabled()
                            ->columnSpan(1)
                            ->helperText('3X-UI inbound ID (read-only)'),

                        TextInput::make('tag')
                            ->label('Inbound Tag')
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Unique identifier for this inbound'),

                        TextInput::make('remark')
                            ->label('Inbound Name/Remark')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2)
                            ->helperText('Descriptive name for this inbound'),

                        Toggle::make('enable')
                            ->label('Enabled')
                            ->required()
                            ->default(true)
                            ->columnSpan(1)
                            ->helperText('Enable/disable this inbound'),

                        Toggle::make('is_default')
                            ->label('Default Inbound')
                            ->default(false)
                            ->columnSpan(1)
                            ->helperText('Use as default for new clients'),
                    ])->columns(2),

                    Section::make('🌐 Network Configuration')->schema([
                        TextInput::make('listen')
                            ->label('Listen IP')
                            ->placeholder('0.0.0.0')
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('IP address to bind to'),

                        TextInput::make('port')
                            ->label('Port')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->columnSpan(1)
                            ->helperText('Port number for this inbound'),

                        Select::make('protocol')
                            ->label('Protocol')
                            ->required()
                            ->options([
                                'vless' => 'VLESS',
                                'vmess' => 'VMESS',
                                'trojan' => 'TROJAN',
                                'shadowsocks' => 'Shadowsocks',
                                'socks' => 'SOCKS',
                                'http' => 'HTTP',
                            ])
                            ->columnSpan(1)
                            ->helperText('Protocol type for this inbound'),

                        Select::make('status')
                            ->label('Local Status')
                            ->options([
                                'active' => '🟢 Active',
                                'inactive' => '⭕ Inactive',
                                'error' => '🔴 Error',
                                'full' => '🟡 Full Capacity',
                                'maintenance' => '🔧 Maintenance',
                            ])
                            ->default('active')
                            ->columnSpan(1)
                            ->helperText('Current operational status'),
                    ])->columns(2),
                ])->columnSpan(2)->visibleOn('edit'),

                Group::make()->schema([
                    Section::make('⚙️ Provisioning & Capacity')->schema([
                        Toggle::make('provisioning_enabled')
                            ->label('Auto-Provisioning')
                            ->default(true)
                            ->helperText('Enable automatic client provisioning'),

                        TextInput::make('capacity')
                            ->label('Max Clients')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('Unlimited')
                            ->helperText('Maximum number of clients (blank = unlimited)'),

                        TextInput::make('current_clients')
                            ->label('Current Clients')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Current active client count'),
                    ]),

                    Section::make('📊 Traffic & Throughput')
                        ->description('Read-only usage since last sync; values shown in MB for convenience')
                        ->schema([
                        Forms\Components\Placeholder::make('traffic_display')
                            ->label('Traffic Usage')
                            ->content(function ($record) {
                                if (!$record) return 'No data';

                                $up = $record->up ?? 0;
                                $down = $record->down ?? 0;
                                $total = $up + $down;

                                return "↑ " . number_format($up / 1024 / 1024, 2) . " MB\n" .
                                       "↓ " . number_format($down / 1024 / 1024, 2) . " MB\n" .
                                       "Total: " . number_format($total / 1024 / 1024, 2) . " MB";
                            }),
                    ])->hidden(fn ($context) => $context === 'create'),

                    Section::make('🔄 Sync Status')->schema([
                        Forms\Components\Placeholder::make('last_sync')
                            ->label('Last Sync')
                            ->content(fn ($record) => $record?->last_api_sync_at?->diffForHumans() ?? 'Never'),

                        Forms\Components\Placeholder::make('sync_status')
                            ->label('Sync Status')
                            ->content(fn ($record) => $record?->api_sync_status ?? 'Unknown'),
                    ])->hidden(fn ($context) => $context === 'create'),
                ])->columnSpan(1)->visibleOn('edit'),

                Group::make()->schema([
                    Section::make('🔧 Advanced 3X-UI Configuration')->schema([
                        Forms\Components\Textarea::make('settings')
                            ->label('Settings JSON')
                            ->rows(4)
                            ->placeholder('{"clients": [], "decryption": "none"}')
                            ->helperText('3X-UI settings configuration (JSON format)'),

                        Forms\Components\Textarea::make('streamSettings')
                            ->label('Stream Settings JSON')
                            ->rows(4)
                            ->placeholder('{"network": "tcp", "security": "none"}')
                            ->helperText('3X-UI stream settings configuration (JSON format)'),

                        Forms\Components\Textarea::make('sniffing')
                            ->label('Sniffing JSON')
                            ->rows(3)
                            ->placeholder('{"enabled": false, "destOverride": ["http", "tls"]}')
                            ->helperText('3X-UI sniffing configuration (JSON format)'),

                        Forms\Components\Textarea::make('allocate')
                            ->label('Allocate JSON')
                            ->rows(3)
                            ->placeholder('{"strategy": "always", "refresh": 5}')
                            ->helperText('3X-UI allocation configuration (JSON format)'),
                    ])
                ])->columnSpanFull()->visibleOn('edit'),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                Tables\Columns\IconColumn::make('enable')
                    ->label('Enabled')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip(fn ($record) => "Server: {$record->server?->name}"),

                Tables\Columns\TextColumn::make('remark')
                    ->label('Inbound Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),

                Tables\Columns\TextColumn::make('tag')
                    ->label('Tag')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No tag'),

                Tables\Columns\TextColumn::make('protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vless' => 'success',
                        'vmess' => 'info',
                        'trojan' => 'warning',
                        'shadowsocks' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'vless' => 'heroicon-o-shield-check',
                        'vmess' => 'heroicon-o-globe-alt',
                        'trojan' => 'heroicon-o-lock-closed',
                        'shadowsocks' => 'heroicon-o-eye-slash',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('port')
                    ->label('Port')
                    ->sortable()
                    ->alignCenter()
                    ->copyable(),

                Tables\Columns\TextColumn::make('current_clients')
                    ->label('Clients')
                    ->alignCenter()
                    ->sortable()
                    ->color(function ($record) {
                        if (!$record->capacity) return 'info';
                        $utilization = $record->getCapacityUtilization();
                        return $utilization > 80 ? 'danger' : ($utilization > 50 ? 'warning' : 'success');
                    })
                    ->description(fn ($record) => $record->capacity ? "/ {$record->capacity}" : 'Unlimited'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'error' => 'danger',
                        'full' => 'warning',
                        'maintenance' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-pause-circle',
                        'error' => 'heroicon-o-x-circle',
                        'full' => 'heroicon-o-exclamation-triangle',
                        'maintenance' => 'heroicon-o-wrench',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('traffic_display')
                    ->label('Traffic')
                    ->getStateUsing(function ($record) {
                        $total = ($record->up ?? 0) + ($record->down ?? 0);
                        return number_format($total / 1024 / 1024, 1) . ' MB';
                    })
                    ->sortable(['up', 'down'])
                    ->alignRight(),

                Tables\Columns\IconColumn::make('provisioning_enabled')
                    ->label('Auto-Provision')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_api_sync_at')
                    ->label('Last Sync')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('protocol')
                    ->options([
                        'vless' => 'VLESS',
                        'vmess' => 'VMESS',
                        'trojan' => 'TROJAN',
                        'shadowsocks' => 'Shadowsocks',
                        'socks' => 'SOCKS',
                        'http' => 'HTTP',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'error' => 'Error',
                        'full' => 'Full Capacity',
                        'maintenance' => 'Maintenance',
                    ]),

                Tables\Filters\TernaryFilter::make('enable')
                    ->label('Enabled')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('provisioning_enabled')
                    ->label('Auto-Provisioning')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled')
                    ->native(false),

                Tables\Filters\Filter::make('high_capacity')
                    ->label('High Usage (>80%)')
                    ->query(function ($query) {
                        return $query->whereRaw('current_clients / NULLIF(capacity, 0) > 0.8');
                    }),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->color('info'),

                    \Filament\Actions\EditAction::make()
                        ->color('warning'),

                    Action::make('sync_from_xui')
                        ->label('🔄 Sync from XUI')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function ($record) {
                            try {
                                $xui = new XUIService($record->server);
                                $remoteInbounds = $xui->listInbounds();

                                // Find matching inbound by remote_id or tag
                                $matchingInbound = collect($remoteInbounds)->first(function ($inbound) use ($record) {
                                    return ($record->remote_id && $inbound['id'] == $record->remote_id) ||
                                           ($record->tag && $inbound['tag'] == $record->tag);
                                });

                                if ($matchingInbound) {
                                    $record->update([
                                        'remote_id' => $matchingInbound['id'],
                                        'port' => $matchingInbound['port'],
                                        'protocol' => $matchingInbound['protocol'],
                                        'enable' => $matchingInbound['enable'],
                                        'up' => $matchingInbound['up'] ?? 0,
                                        'down' => $matchingInbound['down'] ?? 0,
                                        'total' => $matchingInbound['total'] ?? 0,
                                        'settings' => is_array($matchingInbound['settings']) ? json_encode($matchingInbound['settings']) : $matchingInbound['settings'],
                                        'streamSettings' => is_array($matchingInbound['streamSettings']) ? json_encode($matchingInbound['streamSettings']) : $matchingInbound['streamSettings'],
                                        'last_api_sync_at' => now(),
                                        'api_sync_status' => 'success',
                                    ]);

                                    \Filament\Notifications\Notification::make()
                                        ->title('🔄 Sync Successful')
                                        ->body("Inbound '{$record->remark}' synced successfully from 3X-UI")
                                        ->success()
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('❌ Sync Failed')
                                        ->body("Inbound not found on remote 3X-UI server")
                                        ->danger()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Log::error("Inbound sync failed for {$record->remark}: " . $e->getMessage());

                                \Filament\Notifications\Notification::make()
                                    ->title('❌ Sync Error')
                                    ->body("Failed to sync: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('view_clients')
                        ->label('👥 View Clients')
                        ->icon('heroicon-o-users')
                        ->color('success')
                        ->url(fn ($record) => \App\Filament\Clusters\ServerManagement\Resources\ServerClientResource::getUrl('index', [
                            'tableFilters[server_inbound_id][value]' => $record->id,
                        ]))
                        ->openUrlInNewTab(),

                    Action::make('toggle_provisioning')
                        ->label(fn ($record) => $record->provisioning_enabled ? 'Disable Provisioning' : 'Enable Provisioning')
                        ->icon(fn ($record) => $record->provisioning_enabled ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->color(fn ($record) => $record->provisioning_enabled ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update(['provisioning_enabled' => !$record->provisioning_enabled]);

                            \Filament\Notifications\Notification::make()
                                ->title('⚙️ Provisioning Updated')
                                ->body("Auto-provisioning " . ($record->provisioning_enabled ? 'enabled' : 'disabled'))
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\DeleteAction::make()
                        ->color('danger'),
                ])
                ->label('Actions')
                ->color('gray')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),

                    \Filament\Actions\BulkAction::make('sync_all')
                        ->label('🔄 Sync All from XUI')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function ($records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $record) {
                                try {
                                    $xui = new XUIService($record->server);
                                    $remoteInbounds = $xui->listInbounds();

                                    $matchingInbound = collect($remoteInbounds)->first(function ($inbound) use ($record) {
                                        return ($record->remote_id && $inbound['id'] == $record->remote_id) ||
                                               ($record->tag && $inbound['tag'] == $record->tag);
                                    });

                                    if ($matchingInbound) {
                                        $record->update([
                                            'last_api_sync_at' => now(),
                                            'api_sync_status' => 'success',
                                        ]);
                                        $successCount++;
                                    } else {
                                        $failCount++;
                                    }
                                } catch (\Exception $e) {
                                    $failCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('🔄 Bulk Sync Complete')
                                ->body("✅ Synced: {$successCount}, ❌ Failed: {$failCount}")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('enable_provisioning')
                        ->label('Enable Provisioning')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['provisioning_enabled' => true]);
                            \Filament\Notifications\Notification::make()
                                ->title('⚙️ Provisioning Enabled')
                                ->body(count($records) . ' inbounds now have auto-provisioning enabled.')
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('disable_provisioning')
                        ->label('Disable Provisioning')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['provisioning_enabled' => false]);
                            \Filament\Notifications\Notification::make()
                                ->title('⚙️ Provisioning Disabled')
                                ->body(count($records) . ' inbounds have auto-provisioning disabled.')
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-code-bracket',
                'heading' => 'No inbounds found',
                'description' => 'Try broadening your filters.',
            ],
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
            'index' => Pages\ListServerInbounds::route('/'),
            'create' => Pages\CreateServerInbound::route('/create'),
            'view' => Pages\ViewServerInbound::route('/{record}'),
            'edit' => Pages\EditServerInbound::route('/{record}/edit'),
        ];
    }

    // View Infolist
    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Inbound Details')
                ->persistTab()
                ->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-m-eye')
                        ->schema([
                            InfolistSection::make('Summary')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])
                                ->schema([
                                    TextEntry::make('server.name')->label('Server')->color('primary'),
                                    TextEntry::make('remark')->label('Remark')->weight('medium'),
                                    TextEntry::make('tag')->label('Tag')->badge()->color('info'),
                                    TextEntry::make('protocol')->label('Protocol')->badge(),
                                    IconEntry::make('enable')->label('Enabled')->boolean(),
                                    TextEntry::make('status')->label('Local Status')->badge(),
                                ]),
                        ]),

                    Tabs\Tab::make('Network')
                        ->icon('heroicon-m-globe-alt')
                        ->schema([
                            InfolistSection::make('Connectivity')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('listen')->label('Listen IP'),
                                    TextEntry::make('port')->label('Port'),
                                ]),
                        ]),

                    Tabs\Tab::make('Provisioning')
                        ->icon('heroicon-m-cog-8-tooth')
                        ->schema([
                            InfolistSection::make('Capacity')
                                ->columns(3)
                                ->schema([
                                    IconEntry::make('provisioning_enabled')->label('Auto-Provisioning')->boolean(),
                                    TextEntry::make('current_clients')->label('Current Clients')->badge(),
                                    TextEntry::make('capacity')->label('Max Clients')->badge()->placeholder('Unlimited'),
                                ]),
                        ]),

                    Tabs\Tab::make('Sync & Traffic')
                        ->icon('heroicon-m-arrow-path')
                        ->schema([
                            InfolistSection::make('Sync')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('last_api_sync_at')->label('Last API Sync')->since(),
                                    TextEntry::make('api_sync_status')->label('Sync Status')->badge(),
                                ]),

                            InfolistSection::make('Traffic')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('up')->label('Upload')->formatStateUsing(fn($s) => number_format(($s ?? 0)/1024/1024, 2) . ' MB'),
                                    TextEntry::make('down')->label('Download')->formatStateUsing(fn($s) => number_format(($s ?? 0)/1024/1024, 2) . ' MB'),
                                    TextEntry::make('total')->label('Total')->formatStateUsing(fn($s) => number_format(($s ?? 0)/1024/1024, 2) . ' MB'),
                                ]),
                        ]),
                ])
                ->contained(true)
                ->columnSpanFull(),
        ]);
    }
}
