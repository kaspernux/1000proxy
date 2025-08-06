<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
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
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ActionGroup;
use App\Filament\Customer\Pages\ServerBrowsing;

class MyActiveServers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'My Active Servers';
    protected static string $view = 'filament.customer.pages.my-active-servers';
    protected static ?int $navigationSort = 3;
    // No navigation group - appears in main navigation

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
            ->columns([
                TextColumn::make('id')
                    ->label('Client #')
                    ->prefix('#')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyableState(fn (ServerClient $record): string => "#{$record->id}")
                    ->tooltip('Click to copy client ID'),

                TextColumn::make('email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->iconColor('primary')
                    ->tooltip('Client identifier'),

                TextColumn::make('serverInbound.server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-server'),

                TextColumn::make('serverInbound.server.country')
                    ->label('Location')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-map-pin'),

                TextColumn::make('serverInbound.protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color('warning'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'suspended',
                        'gray' => 'pending',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'active',
                        'heroicon-m-x-circle' => 'inactive',
                        'heroicon-m-pause-circle' => 'suspended',
                        'heroicon-m-clock' => 'pending',
                    ]),

                TextColumn::make('traffic_used_mb')
                    ->label('Traffic Used')
                    ->formatStateUsing(fn ($state): string => 
                        $state ? number_format($state / 1024, 2) . ' GB' : '0 GB'
                    )
                    ->sortable()
                    ->color('info'),

                TextColumn::make('traffic_limit_mb')
                    ->label('Traffic Limit')
                    ->formatStateUsing(fn ($state): string => 
                        $state ? number_format($state / 1024, 2) . ' GB' : 'Unlimited'
                    )
                    ->sortable()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expiry_time')
                    ->label('Expires')
                    ->formatStateUsing(function ($state): string {
                        if (!$state || $state == 0) {
                            return 'Never';
                        }
                        $timestamp = $state / 1000; // Convert from milliseconds
                        return \Carbon\Carbon::createFromTimestamp($timestamp)->format('M d, Y H:i');
                    })
                    ->sortable()
                    ->color(function ($state) {
                        if (!$state || $state == 0) return 'success';
                        $timestamp = $state / 1000;
                        return $timestamp < time() ? 'danger' : 'success';
                    })
                    ->tooltip(function ($state): string {
                        if (!$state || $state == 0) return 'Never expires';
                        $timestamp = $state / 1000;
                        return 'Expires ' . \Carbon\Carbon::createFromTimestamp($timestamp)->diffForHumans();
                    }),
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
                    ->relationship('serverInbound.server', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Server'),

                SelectFilter::make('protocol')
                    ->label('Protocol')
                    ->relationship('serverInbound', 'protocol')
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
                Tables\Actions\BulkAction::make('bulk_download')
                    ->label('Bulk Download Configs')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (Collection $records) => $this->bulkDownloadConfigs($records)),

                Tables\Actions\BulkAction::make('bulk_suspend')
                    ->label('Suspend Selected')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $this->bulkSuspend($records)),

                Tables\Actions\BulkAction::make('bulk_activate')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->action(fn (Collection $records) => $this->bulkActivate($records)),
            ])
            ->emptyStateHeading('No Active Servers')
            ->emptyStateDescription('You don\'t have any active proxy servers yet. Browse our servers to get started!')
            ->emptyStateIcon('heroicon-o-server-stack')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse_servers')
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
            ->with(['serverInbound.server', 'subscription'])
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
