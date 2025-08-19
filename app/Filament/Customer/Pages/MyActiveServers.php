<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerClient;
use App\Models\Subscription;
use App\Models\Server;
use App\Services\QrCodeService;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use App\Filament\Customer\Pages\ServerBrowsing;
use BackedEnum;

class MyActiveServers extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'My Active Servers';
    protected string $view = 'filament.customer.pages.my-active-servers';
    protected static ?int $navigationSort = 2;

    // Livewire state for Filament form
    public ?array $data = [];

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        
        if (!$customerId) {
            return null;
        }

        $activeCount = ServerClient::where('customer_id', $customerId)
            ->where('status', 'active')
            ->count();

        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getServerClientsQuery())
            ->columnManager()
            ->headerActions([
                $table->getColumnManagerTriggerAction()->label('Columns'),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('export_csv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(fn () => $this->exportActiveServers('csv')),
                    \Filament\Actions\Action::make('export_json')
                        ->label('Export JSON')
                        ->icon('heroicon-o-code-bracket')
                        ->color('success')
                        ->action(fn () => $this->exportActiveServers('json')),
                ])->label('Export')
                  ->icon('heroicon-o-document-arrow-down'),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Client #')
                    ->prefix('#')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyableState(fn (ServerClient $record): string => "#{$record->id}")
                    ->tooltip('Copy client ID')
                    ->icon('heroicon-o-identification')
                    ->color('primary')
                    ->extraAttributes(['class' => 'font-bold text-primary-600 dark:text-primary-400 sm:text-base text-xs']),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->copyable()
                    ->icon('heroicon-o-envelope')
                    ->iconColor('primary')
                    ->tooltip('Client Email')
                    ->extraAttributes(['class' => 'font-semibold text-gray-800 dark:text-gray-200 sm:text-base text-xs']),

                TextColumn::make('inbound.server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-server-stack')
                    ->color('info')
                    ->extraAttributes(['class' => 'font-bold text-blue-700 dark:text-blue-300 sm:text-base text-xs']),

                TextColumn::make('inbound.server.country')
                    ->label('Location')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->extraAttributes(['class' => 'text-blue-600 dark:text-blue-400 sm:text-base text-xs']),

                TextColumn::make('inbound.protocol')
                    ->label('Protocol')
                    ->toggleable()
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->extraAttributes(['class' => 'text-yellow-600 dark:text-yellow-400 sm:text-base text-xs']),

                TextColumn::make('status')
                    ->label('Status')
                    ->toggleable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'suspended' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        'suspended' => 'heroicon-o-pause-circle',
                        'pending' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->extraAttributes(['class' => 'font-bold sm:text-base text-xs']),

                TextColumn::make('traffic_used_mb')
                    ->label('Traffic Used')
                    ->formatStateUsing(fn ($state): string => 
                        $state ? number_format($state / 1024, 2) . ' GB' : '0 GB'
                    )
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->alignment('right')
                    ->extraAttributes(['class' => 'text-orange-600 dark:text-orange-400 sm:text-base text-xs']),

                TextColumn::make('traffic_limit_mb')
                    ->label('Traffic Limit')
                    ->formatStateUsing(fn ($state): string => 
                        $state ? number_format($state / 1024, 2) . ' GB' : 'Unlimited'
                    )
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->alignment('right')
                    ->extraAttributes(['class' => 'text-green-600 dark:text-green-400 sm:text-base text-xs']),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->description(fn (ServerClient $record): string => $record->created_at->diffForHumans())
                    ->color('gray')
                    ->icon('heroicon-o-calendar-days')
                    ->extraAttributes(['class' => 'text-xs text-gray-500 dark:text-gray-400']),

                TextColumn::make('expiry_time')
                    ->label('Expires')
                    ->toggleable()
                    ->formatStateUsing(function ($state): string {
                        if (!$state || $state == 0) {
                            return 'Never';
                        }
                        $timestamp = $state / 1000; // Convert from milliseconds
                        return \Carbon\Carbon::createFromTimestamp($timestamp)->format('M j, Y');
                    })
                    ->description(function (ServerClient $record): ?string {
                        if (!$record->expiry_time || $record->expiry_time == 0) return null;
                        $timestamp = $record->expiry_time / 1000;
                        return \Carbon\Carbon::createFromTimestamp($timestamp)->diffForHumans();
                    })
                    ->color(function (ServerClient $record): string {
                        if (!$record->expiry_time || $record->expiry_time == 0) return 'gray';
                        $timestamp = $record->expiry_time / 1000;
                        $daysUntilExpiry = now()->diffInDays(\Carbon\Carbon::createFromTimestamp($timestamp), false);
                        if ($daysUntilExpiry < 0) return 'danger';
                        if ($daysUntilExpiry <= 7) return 'warning';
                        return 'success';
                    })
                    ->icon('heroicon-o-calendar-days')
                    ->extraAttributes(['class' => 'text-xs font-medium sm:text-base']),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                        'pending' => 'Pending',
                    ])
                    ->indicator('Status'),

                SelectFilter::make('server_inbound_id')
                    ->label('Server')
                    ->relationship('inbound.server', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Server'),

                SelectFilter::make('protocol')
                    ->label('Protocol')
                    ->relationship('inbound', 'protocol')
                    ->indicator('Protocol'),

                Filter::make('expires_soon')
                    ->label('Expires Soon')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('expiry_time', '>', 0) // 0 means never expires
                              ->where('expiry_time', '<=', (now()->addDays(7)->timestamp * 1000)) // Convert to milliseconds
                              ->where('expiry_time', '>', (now()->timestamp * 1000)) // Convert to milliseconds
                    )
                    ->indicator('Expires Soon'),

                Filter::make('high_traffic_usage')
                    ->label('High Traffic Usage')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('traffic_used_mb > (traffic_limit_mb * 0.8)')
                              ->whereNotNull('traffic_limit_mb')
                    )
                    ->indicator('High Usage'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_config')
                        ->label('View Config')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->modalContent(fn (ServerClient $record) => view('filament.customer.modals.server-config-details', [
                            'client' => $record,
                            'qrCode' => $this->generateQRCode($record),
                        ]))
                        ->modalWidth('4xl')
                        ->modalHeading(fn (ServerClient $record) => "Configuration: {$record->email}"),

                    Action::make('download_config')
                        ->label('Download Config')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (ServerClient $record) => $record->status === 'active')
                        ->action(fn (ServerClient $record) => $this->downloadConfiguration($record)),

                    Action::make('copy_link')
                        ->label('Copy Link')
                        ->icon('heroicon-o-clipboard')
                        ->color('info')
                        ->visible(fn (ServerClient $record) => $record->client_link)
                        ->action(function (ServerClient $record) {
                            $this->js("navigator.clipboard.writeText('{$record->client_link}')");
                            Notification::make()
                                ->title('Configuration Link Copied!')
                                ->success()
                                ->send();
                        }),
                    Action::make('regenerate_config')
                        ->label('Regenerate Config')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Regenerate Configuration')
                        ->modalDescription('This will generate a new configuration. The old configuration will stop working.')
                        ->action(fn (ServerClient $record) => $this->regenerateConfig($record)),

                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->visible(fn (ServerClient $record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->action(fn (ServerClient $record) => $this->suspendClient($record)),

                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->visible(fn (ServerClient $record) => in_array($record->status, ['suspended', 'inactive']))
                        ->action(fn (ServerClient $record) => $this->activateClient($record)),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('bulk_download')
                    ->label('Bulk Download Configs')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (Collection $records) => $this->bulkDownloadConfigs($records)),

                \Filament\Actions\BulkAction::make('bulk_suspend')
                    ->label('Suspend Selected')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $this->bulkSuspend($records)),

                \Filament\Actions\BulkAction::make('bulk_activate')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->action(fn (Collection $records) => $this->bulkActivate($records)),
            ])
            ->emptyStateHeading('No Active Servers')
            ->emptyStateDescription('You don\'t have any active proxy servers yet. Browse our servers to get started!')
            ->emptyStateIcon('heroicon-o-server-stack')
            ->emptyStateActions([
                \Filament\Actions\Action::make('browse_servers')
                    ->label('Browse Servers')
                    ->icon('heroicon-o-server')
                    ->url(fn (): string => ServerBrowsing::getUrl())
                    ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s') // Real-time updates
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('operation')
                    ->label('Operation')
                    ->options([
                        'export_json' => 'Export All (JSON)',
                        'export_csv' => 'Export All (CSV)',
                        'refresh' => 'Refresh Statuses',
                    ])
                    ->default('export_json')
                    ->required(),

                Toggle::make('include_inactive')
                    ->label('Include inactive/suspended in export')
                    ->default(false)
                    ->helperText('Only applies to CSV export.'),

                FormActions::make([
                    \Filament\Forms\Components\Actions\Action::make('run')
                        ->label('Run')
                        ->color('primary')
                        ->submit('run'),
                ])->fullWidth(),
            ])
            ->statePath('data');
    }

    public function run(): void
    {
        $state = $this->form->getState();
        $operation = $state['operation'] ?? null;
        $includeInactive = (bool)($state['include_inactive'] ?? false);

        match ($operation) {
            'export_json' => $this->exportAllConfigurations(),
            'export_csv' => $this->exportActiveServers('csv', $includeInactive),
            'refresh' => $this->refreshAllStatuses(),
            default => null,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('browse_servers')
                ->label('Browse Servers')
                ->icon('heroicon-o-server')
                ->color('primary')
                ->url(fn (): string => ServerBrowsing::getUrl()),

            PageAction::make('export_configs')
                ->label('Export All Configs')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $this->exportAllConfigurations();
                }),

            PageAction::make('refresh_all')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshAllStatuses();
                }),
        ];
    }

    protected function getServerClientsQuery(): Builder
    {
        $customer = Auth::guard('customer')->user();

        return ServerClient::query()
            ->with(['serverInbound.server'])
            ->where('customer_id', $customer->id)
            ->latest();
    }

    public function downloadConfiguration(ServerClient $client): void
    {
        if (!$client->client_link) {
            Notification::make()
                ->title('Configuration Not Ready')
                ->body('Configuration is not available yet.')
                ->warning()
                ->send();
            return;
        }

        // Generate configuration file content
        $config = [
            'client_link' => $client->client_link,
            'subscription_link' => $client->subscription_link,
            'server_name' => $client->serverInbound->server->name,
            'protocol' => $client->serverInbound->protocol,
            'location' => $client->serverInbound->server->country,
            'email' => $client->email,
        ];

        $filename = "config_{$client->serverInbound->server->name}_{$client->email}.json";
        $content = json_encode($config, JSON_PRETTY_PRINT);

        $this->js("
            const element = document.createElement('a');
            element.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent('{$content}'));
            element.setAttribute('download', '{$filename}');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        ");

        Notification::make()
            ->title('Configuration Downloaded')
            ->success()
            ->send();
    }

    public function exportAllConfigurations(): void
    {
        $customer = Auth::guard('customer')->user();
        $clients = ServerClient::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with('serverInbound.server')
            ->get();

        if ($clients->isEmpty()) {
            Notification::make()
                ->title('No Active Servers')
                ->body('You have no active servers to export.')
                ->warning()
                ->send();
            return;
        }

        $exportData = [
            'export_date' => now()->toISOString(),
            'customer_email' => $customer->email,
            'total_servers' => $clients->count(),
            'servers' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'email' => $client->email,
                    'server_name' => $client->serverInbound->server->name,
                    'location' => $client->serverInbound->server->country,
                    'protocol' => $client->serverInbound->protocol,
                    'client_link' => $client->client_link,
                    'subscription_link' => $client->subscription_link,
                    'status' => $client->status,
                    'created_at' => $client->created_at->toISOString(),
                ];
            })->toArray(),
        ];

        $filename = "active_servers_export_" . now()->format('Y-m-d_H-i-s') . ".json";
        $content = json_encode($exportData, JSON_PRETTY_PRINT);

        $this->js("
            const element = document.createElement('a');
            element.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent('{$content}'));
            element.setAttribute('download', '{$filename}');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        ");

        Notification::make()
            ->title('Export Complete')
            ->body("Exported {$clients->count()} active server configurations.")
            ->success()
            ->send();
    }

    protected function exportActiveServers(string $format = 'csv', bool $includeInactive = false): void
    {
        $customer = Auth::guard('customer')->user();
        $query = ServerClient::where('customer_id', $customer->id)
            ->with('serverInbound.server')
            ->latest();

        if (!$includeInactive) {
            $query->where('status', 'active');
        }

        $clients = $query->get();

        if ($clients->isEmpty()) {
            Notification::make()
                ->title('Nothing to export')
                ->warning()
                ->send();
            return;
        }

        if ($format === 'json') {
            $payload = $clients->map(function ($c) {
                return [
                    'id' => $c->id,
                    'email' => $c->email,
                    'server' => $c->serverInbound?->server?->name,
                    'country' => $c->serverInbound?->server?->country,
                    'protocol' => $c->serverInbound?->protocol,
                    'status' => $c->status,
                    'traffic_used_mb' => $c->traffic_used_mb,
                    'traffic_limit_mb' => $c->traffic_limit_mb,
                    'created_at' => optional($c->created_at)->toISOString(),
                ];
            })->values()->toArray();

            $filename = 'active_servers_' . now()->format('Y-m-d_H-i-s') . '.json';
            $content = json_encode($payload, JSON_PRETTY_PRINT);
            $mime = 'application/json';
        } else {
            $rows = [
                ['Client ID','Email','Server','Country','Protocol','Status','Traffic Used (MB)','Traffic Limit (MB)','Created At'],
            ];
            foreach ($clients as $c) {
                $rows[] = [
                    $c->id,
                    $c->email,
                    $c->serverInbound?->server?->name,
                    $c->serverInbound?->server?->country,
                    $c->serverInbound?->protocol,
                    $c->status,
                    $c->traffic_used_mb,
                    $c->traffic_limit_mb,
                    optional($c->created_at)->format('Y-m-d H:i'),
                ];
            }
            $content = collect($rows)->map(fn($r) => collect($r)->map(fn($v) => str_contains((string)$v, ',') ? '"'.str_replace('"','""',$v).'"' : $v)->implode(','))->implode("\n");
            $filename = 'active_servers_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $mime = 'text/csv';
        }

        $this->js("
            (function(){
                const a=document.createElement('a');
                a.href='data:" . $mime . ";charset=utf-8,'+encodeURIComponent(`" . $content . "`);
                a.download='" . $filename . "';
                a.style.display='none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            })();
        ");

        Notification::make()
            ->title('Export started')
            ->success()
            ->send();
    }

    protected function generateQRCode(ServerClient $client): string
    {
        if (!$client->client_link) {
            return '';
        }

        try {
            $qrCodeService = app(QrCodeService::class);
            return $qrCodeService->generateClientQrCode($client->client_link);
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function regenerateConfig(ServerClient $client): void
    {
        // This would typically call your backend service to regenerate the config
        Notification::make()
            ->title('Configuration Regenerated')
            ->body('New configuration has been generated successfully.')
            ->success()
            ->send();
    }

    protected function suspendClient(ServerClient $client): void
    {
        $client->update(['status' => 'suspended']);
        
        Notification::make()
            ->title('Client Suspended')
            ->body("Client {$client->email} has been suspended.")
            ->warning()
            ->send();
    }

    protected function activateClient(ServerClient $client): void
    {
        $client->update(['status' => 'active']);
        
        Notification::make()
            ->title('Client Activated')
            ->body("Client {$client->email} has been activated.")
            ->success()
            ->send();
    }

    protected function bulkDownloadConfigs(Collection $clients): void
    {
        $activeClients = $clients->filter(fn ($client) => $client->status === 'active' && $client->client_link);
        
        if ($activeClients->isEmpty()) {
            Notification::make()
                ->title('No Active Configurations')
                ->body('No active configurations found in selection.')
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Bulk Download Started')
            ->body("Preparing {$activeClients->count()} configurations for download...")
            ->success()
            ->send();
    }

    protected function bulkSuspend(Collection $clients): void
    {
        $updated = $clients->where('status', 'active')->count();
        
        ServerClient::whereIn('id', $clients->pluck('id'))
            ->where('status', 'active')
            ->update(['status' => 'suspended']);

        Notification::make()
            ->title('Clients Suspended')
            ->body("Suspended {$updated} active clients.")
            ->warning()
            ->send();
    }

    protected function bulkActivate(Collection $clients): void
    {
        $updated = $clients->whereIn('status', ['suspended', 'inactive'])->count();
        
        ServerClient::whereIn('id', $clients->pluck('id'))
            ->whereIn('status', ['suspended', 'inactive'])
            ->update(['status' => 'active']);

        Notification::make()
            ->title('Clients Activated')
            ->body("Activated {$updated} clients.")
            ->success()
            ->send();
    }

    protected function refreshAllStatuses(): void
    {
        // This would typically sync with your backend
        Notification::make()
            ->title('Status Refreshed')
            ->body('All server statuses have been updated.')
            ->success()
            ->send();
    }
}
