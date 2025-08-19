<?php
namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServerClient;
use App\Models\ServerPlan;
use App\Models\Server;
use App\Models\ServerInbound;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\RelationManagers;
use App\Services\XUIService;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\View as ViewComponent;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Image;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Notifications\Notification;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;

class ServerClientResource extends Resource
{

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        // Admin/manager manage; support_manager view via policy; sales_support no.
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static ?string $model = ServerClient::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Server Clients';
    protected static ?string $pluralModelLabel = 'Server Clients';
    protected static UnitEnum|string|null $navigationGroup = 'XUI MANAGEMENT';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'server_id';

    public static function getLabel(): string
    {
        return 'Clients';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()->schema([
                    Section::make('🏷️ Client Identity & Configuration')->schema([
                        Select::make('server_inbound_id')
                            ->relationship('inbound', 'remark')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2)
                            ->helperText('Select the inbound this client belongs to'),

                        TextInput::make('email')
                            ->label('Client Email/ID')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Unique email identifier for this client'),

                        TextInput::make('id')
                            ->label('Client UUID')
                            ->placeholder('Auto-generated if empty')
                            ->maxLength(36)
                            ->columnSpan(1)
                            ->helperText('3X-UI client UUID (leave empty for auto-generation)'),

                        Toggle::make('enable')
                            ->label('Enabled')
                            ->required()
                            ->default(true)
                            ->columnSpan(1)
                            ->helperText('Enable/disable this client'),

                        Toggle::make('is_online')
                            ->label('Online Status')
                            ->disabled()
                            ->columnSpan(1)
                            ->helperText('Current online status (read-only)'),
                    ])->columns(2),

                    Section::make('🌐 Network & Security Configuration')->schema([
                        Select::make('flow')
                            ->label('Flow Control')
                            ->options([
                                'xtls-rprx-vision' => 'XTLS-RPRX-Vision',
                                'xtls-rprx-direct' => 'XTLS-RPRX-Direct',
                                '' => 'None',
                            ])
                            ->columnSpan(1)
                            ->helperText('3X-UI flow control method'),

                        TextInput::make('limit_ip')
                            ->label('IP Connection Limit')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(2)
                            ->columnSpan(1)
                            ->helperText('Maximum concurrent IP connections'),

                        TextInput::make('tg_id')
                            ->label('Telegram ID')
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Optional Telegram user ID for notifications'),

                        TextInput::make('sub_id')
                            ->label('Subscription ID')
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('3X-UI subscription identifier'),
                    ])->columns(2),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('📊 Traffic & Limits')->schema([
                        TextInput::make('total_gb_bytes')
                            ->label('Traffic Limit (Bytes)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('Unlimited')
                            ->helperText('Total traffic limit in bytes'),

                        Forms\Components\Placeholder::make('traffic_display')
                            ->label('Traffic Usage')
                            ->content(function ($record) {
                                if (!$record) return 'No data';

                                $up = $record->remote_up ?? 0;
                                $down = $record->remote_down ?? 0;
                                $total = $up + $down;

                                return "↑ " . number_format($up / 1024 / 1024, 2) . " MB\n" .
                                       "↓ " . number_format($down / 1024 / 1024, 2) . " MB\n" .
                                       "Total: " . number_format($total / 1024 / 1024, 2) . " MB";
                            })
                            ->hidden(fn ($context) => $context === 'create'),
                    ]),

                    Section::make('⏰ Timing & Expiry')->schema([
                        Forms\Components\DateTimePicker::make('expiry_time_display')
                            ->label('Expiry Date')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    // Convert datetime to milliseconds timestamp for 3X-UI
                                    $set('expiry_time', Carbon::parse($state)->timestamp * 1000);
                                }
                            })
                            ->helperText('Client expiry date and time'),

                        TextInput::make('expiry_time')
                            ->label('Expiry Timestamp (ms)')
                            ->numeric()
                            ->disabled()
                            ->helperText('3X-UI expiry timestamp in milliseconds'),
                    ]),

                    Section::make('🔄 Sync Status')->schema([
                        Forms\Components\Placeholder::make('last_sync')
                            ->label('Last API Sync')
                            ->content(fn ($record) => $record?->last_api_sync_at?->diffForHumans() ?? 'Never'),

                        Forms\Components\Placeholder::make('sync_status')
                            ->label('Sync Status')
                            ->content(fn ($record) => $record?->api_sync_status ?? 'Unknown'),
                    ])->hidden(fn ($context) => $context === 'create'),
                ])->columnSpan(1),

                Group::make()->schema([
                    Section::make('🏢 Business Information')->schema([
                        Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1)
                            ->helperText('Associated service plan'),

                        Select::make('order_id')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->columnSpan(1)
                            ->helperText('Associated order'),

                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->columnSpan(1)
                            ->helperText('Associated customer'),

                        Select::make('status')
                            ->options([
                                'pending' => '⏳ Pending',
                                'active' => '✅ Active',
                                'suspended' => '⏸️ Suspended',
                                'terminated' => '🔴 Terminated',
                                'expired' => '⏰ Expired',
                            ])
                            ->default('pending')
                            ->columnSpan(1)
                            ->helperText('Current client status'),
                    ])->columns(2),
                ])->columnSpanFull()
                ->visibleOn('edit'),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
        ->columns([
            // ✅ Essential identification columns
            TextColumn::make('email')
                ->label('Client Email/ID')
                ->searchable()
                ->sortable()
                ->copyable()
                ->tooltip('3X-UI client identifier'),

            TextColumn::make('inbound.remark')
                ->label('Inbound')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('primary')
                ->tooltip('Associated inbound configuration'),

            // ✅ Status indicators with proper colors
            ToggleColumn::make('enable')
                ->label('Enabled')
                ->tooltip('Toggle client enable/disable'),

            BadgeColumn::make('status')
                ->label('Status')
                ->colors([
                    'warning' => 'pending',
                    'success' => 'active',
                    'danger' => 'suspended',
                    'gray' => 'terminated',
                    'info' => 'expired',
                ])
                ->icons([
                    'heroicon-o-clock' => 'pending',
                    'heroicon-o-check-circle' => 'active',
                    'heroicon-o-pause-circle' => 'suspended',
                    'heroicon-o-x-circle' => 'terminated',
                    'heroicon-o-exclamation-triangle' => 'expired',
                ]),

            BadgeColumn::make('is_online')
                ->label('Online')
                ->colors([
                    'success' => true,
                    'gray' => false,
                ])
                ->formatStateUsing(fn ($state) => $state ? 'Online' : 'Offline'),

            // ✅ Traffic usage with visual indicators
            TextColumn::make('traffic_usage')
                ->label('Traffic Used')
                ->getStateUsing(function ($record) {
                    $up = $record->remote_up ?? 0;
                    $down = $record->remote_down ?? 0;
                    $total = $up + $down;
                    return number_format($total / 1024 / 1024 / 1024, 2) . ' GB';
                })
                ->badge()
                ->color(function ($record) {
                    $up = $record->remote_up ?? 0;
                    $down = $record->remote_down ?? 0;
                    $total = $up + $down;
                    $limit = $record->total_gb_bytes ?? 0;

                    if ($limit == 0) return 'primary'; // Unlimited

                    $percentage = ($total / $limit) * 100;

                    if ($percentage > 90) return 'danger';
                    if ($percentage > 75) return 'warning';
                    if ($percentage > 50) return 'info';
                    return 'success';
                })
                ->tooltip(function ($record) {
                    $up = number_format(($record->remote_up ?? 0) / 1024 / 1024, 2);
                    $down = number_format(($record->remote_down ?? 0) / 1024 / 1024, 2);
                    return "Upload: {$up} MB\nDownload: {$down} MB";
                }),

            TextColumn::make('limit_ip')
                ->label('IP Limit')
                ->badge()
                ->color('info')
                ->tooltip('Maximum concurrent IP connections'),

            // ✅ Expiry information
            TextColumn::make('expiry_time_display')
                ->label('Expires')
                ->getStateUsing(function ($record) {
                    if (!$record->expiry_time) return 'Never';
                    return Carbon::createFromTimestamp($record->expiry_time / 1000)->format('M j, Y H:i');
                })
                ->badge()
                ->color(function ($record) {
                    if (!$record->expiry_time) return 'primary';

                    $expiry = Carbon::createFromTimestamp($record->expiry_time / 1000);
                    $now = Carbon::now();

                    if ($expiry->isPast()) return 'danger';
                    if ($expiry->diffInDays($now) <= 7) return 'warning';
                    if ($expiry->diffInDays($now) <= 30) return 'info';
                    return 'success';
                })
                ->tooltip(function ($record) {
                    if (!$record->expiry_time) return 'No expiry set';

                    $expiry = Carbon::createFromTimestamp($record->expiry_time / 1000);
                    return $expiry->diffForHumans();
                }),

            // ✅ Business relationships
            TextColumn::make('plan.name')
                ->label('Plan')
                ->badge()
                ->color('secondary')
                ->default('No Plan'),

            TextColumn::make('customer.name')
                ->label('Customer')
                ->searchable()
                ->toggleable()
                ->default('No Customer'),

            // ✅ Sync status
            TextColumn::make('last_api_sync_at')
                ->label('Last Sync')
                ->dateTime()
                ->since()
                ->tooltip('Last 3X-UI API synchronization')
                ->toggleable(),

            // ✅ QR Codes with image rendering
            ImageColumn::make('qr_code_client')
                ->label('Client QR')
                ->disk('public')
                ->tooltip('Click to download Client QR')
                ->height(60),

            ImageColumn::make('qr_code_sub')
                ->label('Sub QR')
                ->disk('public')
                ->tooltip('Click to download Sub QR')
                ->height(60),

            ImageColumn::make('qr_code_sub_json')
                ->label('JSON QR')
                ->disk('public')
                ->tooltip('Click to download JSON QR')
                ->height(60),
        ])
        ->filters([
            SelectFilter::make('server_inbound_id')
                ->relationship('inbound', 'remark')
                ->label('Inbound')
                ->searchable()
                ->preload(),

            SelectFilter::make('status')
                ->options([
                    'pending' => 'Pending',
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'terminated' => 'Terminated',
                    'expired' => 'Expired',
                ]),

            TernaryFilter::make('enable')
                ->label('Enabled')
                ->placeholder('All clients')
                ->trueLabel('Enabled only')
                ->falseLabel('Disabled only'),

            TernaryFilter::make('is_online')
                ->label('Online Status')
                ->placeholder('All clients')
                ->trueLabel('Online only')
                ->falseLabel('Offline only'),

            SelectFilter::make('plan_id')
                ->relationship('plan', 'name')
                ->label('Plan')
                ->searchable()
                ->preload(),
        ])
        ->actions([
            ViewAction::make()
                ->tooltip('View client details'),

            EditAction::make()
                ->tooltip('Edit client configuration'),

            Action::make('sync_from_xui')
                ->label('Sync from 3X-UI')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->tooltip('Sync client data from 3X-UI server')
                ->action(function ($record) {
                    try {
                        $xuiService = new XUIService($record->inbound->server);

                        // Get client data from 3X-UI using available method
                        $clientData = $xuiService->getClientByEmail($record->email);

                        if ($clientData) {
                            $record->update([
                                'total_gb_bytes' => $clientData['totalGB'] ?? 0,
                                'remote_up' => $clientData['up'] ?? 0,
                                'remote_down' => $clientData['down'] ?? 0,
                                'enable' => $clientData['enable'] ?? false,
                                'expiry_time' => $clientData['expiryTime'] ?? null,
                                'last_api_sync_at' => now(),
                                'api_sync_status' => 'success',
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Client synced successfully')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Client not found on 3X-UI server');
                        }
                    } catch (\Exception $e) {
                        $record->update([
                            'api_sync_status' => 'error: ' . $e->getMessage(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Sync failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('reset_traffic')
                ->label('Reset Traffic')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->tooltip('Reset client traffic statistics')
                ->requiresConfirmation()
                ->modalHeading('Reset Traffic Statistics')
                ->modalDescription('Are you sure you want to reset traffic statistics for this client?')
                ->action(function ($record) {
                    try {
                        $xuiService = new XUIService($record->inbound->server);

                        // Reset traffic on 3X-UI server
                        $xuiService->resetClientTraffic($record->inbound->id, $record->email);

                        $record->update([
                            'remote_up' => 0,
                            'remote_down' => 0,
                            'last_api_sync_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Traffic reset successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Traffic reset failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

        ])
        ->bulkActions([
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->tooltip('Delete selected clients'),

                Action::make('bulk_reset_traffic')
                    ->label('Reset Traffic for Selected')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->tooltip('Reset traffic for selected clients')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $successful = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            try {
                                $xuiService = new XUIService($record->inbound->server);

                                $xuiService->resetClientTraffic($record->inbound->id, $record->email);

                                $record->update([
                                    'remote_up' => 0,
                                    'remote_down' => 0,
                                    'last_api_sync_at' => now(),
                                ]);

                                $successful++;
                            } catch (\Exception $e) {
                                $failed++;
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Bulk traffic reset completed')
                            ->body("Successful: {$successful}, Failed: {$failed}")
                            ->success()
                            ->send();
                    }),
            ]),
        ])
    ->defaultSort('created_at', 'desc')
    ->poll('30s') // Auto-refresh every 30 seconds for real-time updates
        ->headerActions([
            Action::make('Sync Clients from XUI')
                ->icon('heroicon-o-arrow-path')
                ->label('Sync All Clients')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    $servers = \App\Models\Server::all();

                    foreach ($servers as $server) {
                        try {
                            $xui             = new XUIService($server->id);
                            $remoteInbounds  = $xui->listInbounds();

                            foreach ($remoteInbounds as $inbound) {
                                // 1) Ensure we have a local inbound record
                                $localInbound = ServerInbound::firstOrCreate([
                                    'server_id' => $server->id,
                                    'port'      => $inbound->port,
                                ]);

                                // 2) Decode the inbound settings and extract remote clients
                                $settings    = is_string($inbound->settings)
                                    ? json_decode($inbound->settings, true)
                                    : (array) $inbound->settings;
                                $clients     = $settings['clients'] ?? [];
                                $remoteSubIds = [];

                                // 3) Upsert each remote client, and collect its subId
                                foreach ($clients as $client) {
                                    $remoteSubIds[] = $client['subId'] ?? null;
                                    ServerClient::fromRemoteClient(
                                        (array) $client,
                                        $localInbound->id
                                    );
                                }

                                // 4) Delete any local clients no longer present remotely
                                ServerClient::where('server_inbound_id', $localInbound->id)
                                    ->whereNotIn('subId', array_filter($remoteSubIds))
                                    ->delete();
                            }
                        } catch (\Throwable $e) {
                            Log::error("Client sync failed for server ID {$server->id}: " . $e->getMessage());
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Success')
                        ->body('Clients synced and stale records removed.')
                        ->success()
                        ->send();
                })
            ]);

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-user-group',
                'heading' => 'No clients found',
                'description' => 'Try adjusting your filters.',
            ],
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerClients::route('/'),
            'create' => Pages\CreateServerClient::route('/create'),
            'view' => Pages\ViewServerClient::route('/{record}'),
            'edit' => Pages\EditServerClient::route('/{record}/edit'),
        ];
    }

    // ✅ Infolist for view page
    public static function infolist(Schema $schema): Schema
{
    return $schema->schema([
        Tabs::make('Client Details')
            ->persistTab()
            ->tabs([
                Tabs\Tab::make('Profile')
                    ->icon('heroicon-m-user')
                    ->schema([
                        InfolistSection::make('🔐 Client Information')
                            ->description('Details about this proxy client’s identity and usage limits.')
                            ->columns([
                                'sm' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ])
                            ->schema([
                                TextEntry::make('email')->label('Client Email')->color('primary'),
                                TextEntry::make('password')->label('UUID / Password')->color('primary'),
                                TextEntry::make('subId')->label('Subscription ID')->color('primary'),
                                TextEntry::make('flow')->label('Flow')->color('primary'),
                                TextEntry::make('limit_ip')->label('IP Limit')->color('primary'),
                                TextEntry::make('total_gb_bytes')->label('Total GB')->color('primary')->formatStateUsing(fn ($state) => $state ? round($state / 1073741824, 2) . ' GB' : '0 GB'),
                                TextEntry::make('expiry_time')->label('Expires At')->dateTime()->color('primary'),
                                TextEntry::make('tg_id')->label('Telegram ID')->default('—')->color('primary'),
                                IconEntry::make('enable')->label('Enabled')->boolean(),
                                TextEntry::make('reset')->label('Reset Count')->default(0)->color('primary'),
                            ]),
                    ]),

                Tabs\Tab::make('Server')
                    ->icon('heroicon-m-server')
                    ->schema([
                        InfolistSection::make('📡 Server Configuration')
                            ->description('Details about the proxy server and plan used.')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('inbound.remark')->label('Inbound Remark')->color('primary'),
                                TextEntry::make('plan.name')->label('Plan Name')->default('N/A')->color('primary'),
                            ]),
                    ]),

                Tabs\Tab::make('QR Codes')
                    ->icon('heroicon-m-qr-code')
                    ->schema([
                        InfolistSection::make('📲 Client QR Codes')
                            ->description('Scan or download QR codes to quickly configure supported proxy clients.')
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->schema([
                                ImageEntry::make('qr_code_client')
                                    ->label('Client QR')
                                    ->disk('public')
                                    ->tooltip('Click to open full-size')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => filled($record->qr_code_client)),

                                ImageEntry::make('qr_code_sub')
                                    ->label('Subscription QR')
                                    ->disk('public')
                                    ->tooltip('Click to open full-size')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => filled($record->qr_code_sub)),

                                ImageEntry::make('qr_code_sub_json')
                                    ->label('JSON Subscription QR')
                                    ->disk('public')
                                    ->tooltip('Click to open full-size')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => filled($record->qr_code_sub_json)),
                            ]),
                    ]),
            ])
            ->contained(true)
            ->columnSpanFull(),
    ]);
}

}
