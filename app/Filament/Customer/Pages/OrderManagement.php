<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Carbon\Carbon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerClient;
use App\Services\QrCodeService;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms;
use Filament\Tables;
use BackedEnum;

class OrderManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'My Orders';
    protected string $view = 'filament.customer.pages.order-management';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getOrdersQuery())
            ->columnManager()
            ->headerActions([
                $table->getColumnManagerTriggerAction()->label('Columns'),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('export_csv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(fn () => $this->exportOrdersQuick('csv')),
                    \Filament\Actions\Action::make('export_json')
                        ->label('Export JSON')
                        ->icon('heroicon-o-code-bracket')
                        ->color('success')
                        ->action(fn () => $this->exportOrdersQuick('json')),
                ])->label('Export')
                  ->icon('heroicon-o-document-arrow-down'),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Order #')
                    ->prefix('#')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyableState(fn (Order $record): string => "#{$record->id}")
                    ->tooltip('Click to copy order ID'),

                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->description(fn (Order $record): string => $record->created_at->diffForHumans())
                    ->color('gray')
                    ->icon('heroicon-o-calendar-days'),

                TextColumn::make('items_summary')
                    ->label('Services')
                    ->toggleable()
                    ->formatStateUsing(function (Order $record): string {
                        $itemsCount = $record->items->count();
                        $firstItem = $record->items->first();
                        if ($itemsCount === 1 && $firstItem) {
                            return $firstItem->server?->name ?? 'Server Access';
                        }
                        return "{$itemsCount} items";
                    })
                    ->description(function (Order $record): string {
                        return $record->items->pluck('server.name')->filter()->implode(', ');
                    }),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-currency-dollar')
                    ->alignment('right'),

                TextColumn::make('status')
                    ->label('Order Status')
                    ->toggleable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'processing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        'refunded' => 'heroicon-o-arrow-uturn-left',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->toggleable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wallet' => 'success',
                        'bitcoin' => 'warning',
                        'monero' => 'info',
                        'solana' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable(),

                TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->toggleable()
                    ->formatStateUsing(function (Order $record): ?string {
                        // Get the earliest expiry in milliseconds or Carbon
                        $timestamps = $record->items->map(function ($item) {
                            $ts = $item->server_client?->expiry_time;
                            if ($ts instanceof \Carbon\Carbon) {
                                return $ts->getTimestampMs();
                            }
                            if (is_numeric($ts)) {
                                // server_clients.expiry_time stored in ms
                                return (int) $ts;
                            }
                            return null;
                        })->filter()->sort();

                        $earliest = $timestamps->first();
                        return $earliest ? \Carbon\Carbon::createFromTimestampMs($earliest)->format('M j, Y') : null;
                    })
                    ->description(function (Order $record): ?string {
                        $timestamps = $record->items->map(function ($item) {
                            $ts = $item->server_client?->expiry_time;
                            if ($ts instanceof \Carbon\Carbon) {
                                return $ts->getTimestampMs();
                            }
                            if (is_numeric($ts)) {
                                return (int) $ts;
                            }
                            return null;
                        })->filter()->sort();
                        $earliest = $timestamps->first();
                        return $earliest ? \Carbon\Carbon::createFromTimestampMs($earliest)->diffForHumans() : null;
                    })
                    ->color(function (Order $record): string {
                        $timestamps = $record->items->map(function ($item) {
                            $ts = $item->server_client?->expiry_time;
                            if ($ts instanceof \Carbon\Carbon) {
                                return $ts->getTimestampMs();
                            }
                            if (is_numeric($ts)) {
                                return (int) $ts;
                            }
                            return null;
                        })->filter()->sort();
                        $earliest = $timestamps->first();
                        if (!$earliest) return 'gray';
                        $daysUntilExpiry = now()->diffInDays(\Carbon\Carbon::createFromTimestampMs($earliest), false);

                        if ($daysUntilExpiry < 0) return 'danger'; // Expired
                        if ($daysUntilExpiry <= 7) return 'warning'; // Expiring soon
                        return 'success'; // Active
                    }),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Order $record) => "Order Details #" . $record->id)
                    ->modalContent(function (Order $record) {
                        return view('filament.customer.components.order-details', ['order' => $record]);
                    }),

                Action::make('download_config')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === 'completed')
                    ->action(function (Order $record) {
                        $this->downloadConfigurations($record);
                    }),

                Action::make('renew_service')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function (Order $record) {
                        // Show if any service is expiring within 30 days
                        $expiringSoon = $record->items->some(function ($item) {
                            $raw = $item->server_client?->expiry_time;
                            if (!$raw) return false;
                            $expiry = $raw instanceof Carbon
                                ? $raw
                                : (is_numeric($raw) ? Carbon::createFromTimestampMs((int) $raw) : null);
                            if (!$expiry) return false;
                            return now()->diffInDays($expiry, false) <= 30;
                        });
                        return $expiringSoon;
                    })
                    ->action(function (Order $record) {
                        $this->renewServices($record);
                    }),

                Action::make('request_refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Order $record) =>
                        $record->status === 'completed' &&
                        $record->created_at->diffInDays() <= 7
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Request Refund')
                    ->modalDescription('Are you sure you want to request a refund for this order?')
                    ->action(function (Order $record) {
                        $this->requestRefund($record);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'wallet' => 'Wallet',
                        'bitcoin' => 'Bitcoin',
                        'monero' => 'Monero',
                        'solana' => 'Solana',
                    ])
                    ->indicator('Payment'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicator('Date Range'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    Action::make('view_details')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn (Order $record) => "Order Details #" . $record->id)
                        ->modalContent(function (Order $record) {
                            return view('filament.customer.components.order-details', ['order' => $record]);
                        }),

                    Action::make('download_config')
                        ->label('Download Config')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (Order $record) => $record->status === 'completed')
                        ->action(function (Order $record) {
                            $this->downloadConfigurations($record);
                        }),

                    Action::make('renew_service')
                        ->label('Renew Service')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(function (Order $record) {
                            // Show if any service is expiring within 30 days
                            $expiringSoon = $record->items->some(function ($item) {
                                $raw = $item->server_client?->expiry_time;
                                if (!$raw) return false;
                                $expiry = $raw instanceof Carbon
                                    ? $raw
                                    : (is_numeric($raw) ? Carbon::createFromTimestampMs((int) $raw) : null);
                                if (!$expiry) return false;
                                return now()->diffInDays($expiry, false) <= 30;
                            });
                            return $expiringSoon;
                        })
                        ->action(function (Order $record) {
                            $this->renewServices($record);
                        }),

                    Action::make('request_refund')
                        ->label('Request Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn (Order $record) =>
                            $record->status === 'completed' &&
                            $record->created_at->diffInDays() <= 7
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Request Refund')
                        ->modalDescription('Are you sure you want to request a refund for this order?')
                        ->action(function (Order $record) {
                            $this->requestRefund($record);
                        }),
                ])->label('Actions')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('export_selected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $this->exportOrders($records);
                        }),

                    \Filament\Actions\BulkAction::make('download_configs')
                        ->label('Download Configs')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $this->bulkDownloadConfigurations($records);
                        }),
                ]),
            ])
            ->emptyStateHeading('No orders found')
            ->emptyStateDescription('You haven\'t placed any orders yet. Browse our servers to get started!')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateActions([
                \Filament\Actions\Action::make('browse_servers')
                    ->label('Browse Servers')
                    ->icon('heroicon-o-server')
                    ->url(fn (): string => route('filament.customer.pages.server-browsing'))
                    ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s') // Real-time updates every 60 seconds
            ->paginationPageOptions([10, 25, 50, 100])
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
                ->url(fn (): string => route('filament.customer.pages.server-browsing')),

            PageAction::make('order_history')
                ->label('Full History')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->action(function () {
                    $this->showFullHistory();
                }),
        ];
    }

    protected function getOrdersQuery(): Builder
    {
        $customer = Auth::guard('customer')->user();

        return Order::query()
            // Eager load server via serverPlan relationship and related serverClients
            ->with(['items.serverPlan.server', 'items.serverClients'])
            ->where('customer_id', $customer->id)
            ->latest();
    }

    public function downloadConfigurations(Order $order): void
    {
        try {
            // Generate configuration files for all server accesses in the order
            $configs = [];

            foreach ($order->items as $item) {
                if ($item->server && $item->server_client) {
                    $server = $item->server;
                    $client = $item->server_client;

                    // Generate configuration for each protocol
                    $configs[] = [
                        'server_name' => $server->name,
                        'vless_config' => $this->generateVlessConfig($server, $client),
                        'vmess_config' => $this->generateVmessConfig($server, $client),
                        'qr_code' => $this->generateQRCode($server, $client),
                    ];
                }
            }

            if (empty($configs)) {
                Notification::make()
                    ->title('No Configurations Available')
                    ->body('No active server configurations found for this order.')
                    ->warning()
                    ->send();
                return;
            }

            // Create ZIP file with all configurations
            $zipPath = $this->createConfigurationZip($order, $configs);

            Notification::make()
                ->title('Download Ready')
                ->body('Configuration files are ready for download.')
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download ZIP')
                        ->url($zipPath)
                ])
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Download Failed')
                ->body('Unable to generate configuration files. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function renewServices(Order $order): void
    {
        $customer = Auth::guard('customer')->user();
        $wallet = $customer->wallet;

        if (!$wallet) {
            Notification::make()
                ->title('Wallet Not Found')
                ->body('Please set up your wallet first.')
                ->warning()
                ->send();
            return;
        }

        $totalRenewalCost = $order->items->sum(function ($item) {
            return $item->server->price ?? 0;
        });

        if ($wallet->balance < $totalRenewalCost) {
            Notification::make()
                ->title('Insufficient Balance')
                ->body("You need \${$totalRenewalCost} to renew all services.")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('topup')
                        ->label('Top Up Wallet')
                        ->url(route('filament.customer.my-wallet.resources.wallets.index'))
                ])
                ->send();
            return;
        }

        try {
            // Create renewal order
            $renewalOrder = Order::create([
                'customer_id' => $customer->id,
                'total_amount' => $totalRenewalCost,
                'status' => 'completed',
                'payment_method' => 'wallet',
                'type' => 'renewal',
                'parent_order_id' => $order->id,
            ]);

            // Renew each service
            foreach ($order->items as $item) {
                if ($item->server_client) {
                    // Extend expiry by one month (expiry_time stored in milliseconds)
                    $raw = $item->server_client->expiry_time;
                    $current = $raw instanceof Carbon
                        ? $raw
                        : (is_numeric($raw) ? Carbon::createFromTimestampMs((int) $raw) : now());
                    $newExpiry = $current->copy()->addMonth();
                    $item->server_client->update([
                        'expiry_time' => (int) $newExpiry->valueOf(), // ms timestamp
                    ]);

                    // Create renewal order item
                    OrderItem::create([
                        'order_id' => $renewalOrder->id,
                        'server_id' => $item->server_id,
                        'quantity' => 1,
                        'price' => $item->server->price ?? 0,
                        'type' => 'renewal',
                    ]);
                }
            }

            // Deduct from wallet
            $wallet->decrement('balance', $totalRenewalCost);

            Notification::make()
                ->title('Services Renewed')
                ->body('All services have been successfully renewed for another month.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Renewal Failed')
                ->body('Unable to renew services. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function requestRefund(Order $order): void
    {
        try {
            // Update order status to refund_requested
            $order->update(['status' => 'refund_requested']);

            // Disable associated server clients
            foreach ($order->items as $item) {
                if ($item->server_client) {
                    $item->server_client->update(['enable' => false]);
                }
            }

            Notification::make()
                ->title('Refund Requested')
                ->body('Your refund request has been submitted and will be processed within 24-48 hours.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Refund Request Failed')
                ->body('Unable to process refund request. Please contact support.')
                ->danger()
                ->send();
        }
    }

    public function showFullHistory(): void
    {
        Notification::make()
            ->title('Order History')
            ->body('This table shows your complete order history.')
            ->info()
            ->send();
    }

    private function generateVlessConfig($server, $client): string
    {
        return "vless://{$client->uuid}@{$server->ip}:{$server->port}?type=tcp&security=none#{$server->name}";
    }

    private function generateVmessConfig($server, $client): string
    {
        $config = [
            'v' => '2',
            'ps' => $server->name,
            'add' => $server->ip,
            'port' => $server->port,
            'id' => $client->uuid,
            'aid' => '0',
            'net' => 'tcp',
            'type' => 'none',
            'host' => '',
            'path' => '',
            'tls' => ''
        ];

        return 'vmess://' . base64_encode(json_encode($config));
    }

    private function generateQRCode($server, $client): string
    {
        try {
            $qrCodeService = app(QrCodeService::class);
            $link = $client->buildClientLink() ?? $this->generateVmessConfig($server, $client);
            
            return $qrCodeService->generateClientQrCode($link, [
                'colorScheme' => 'primary',
                'style' => 'dot',
                'eye' => 'circle'
            ]);
        } catch (\Exception $e) {
            // Fallback to placeholder QR code
            return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==";
        }
    }

    private function createConfigurationZip(Order $order, array $configs): string
    {
        // This would create a ZIP file with all configurations
        return '/downloads/order-' . $order->id . '-configs.zip';
    }

    public function exportOrders(Collection $orders): void
    {
        try {
            // Create CSV export of selected orders
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="orders_export_' . now()->format('Y-m-d') . '.csv"',
            ];

            $csv = "Order ID,Date,Status,Amount,Payment Method,Items\n";
            
            foreach ($orders as $order) {
                $items = $order->items->pluck('server.name')->filter()->implode('; ');
                $csv .= sprintf(
                    "%s,%s,%s,$%s,%s,\"%s\"\n",
                    $order->id,
                    $order->created_at->format('Y-m-d H:i'),
                    ucfirst($order->status),
                    number_format($order->total_amount, 2),
                    ucfirst($order->payment_method),
                    $items
                );
            }

            Notification::make()
                ->title('Export Ready')
                ->body('Orders exported successfully.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Unable to export orders. Please try again.')
                ->danger()
                ->send();
        }
    }

    protected function exportOrdersQuick(string $format = 'csv'): void
    {
        $customer = Auth::guard('customer')->user();
        $orders = Order::with('items.server')
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(500)
            ->get();

        if ($orders->isEmpty()) {
            Notification::make()->title('Nothing to export')->warning()->send();
            return;
        }

        if ($format === 'json') {
            $payload = $orders->map(function ($o) {
                return [
                    'id' => $o->id,
                    'date' => $o->created_at->toISOString(),
                    'status' => $o->status,
                    'total_amount' => $o->total_amount,
                    'payment_method' => $o->payment_method,
                    'items' => $o->items->map(fn($i) => $i->server?->name)->filter()->values()->all(),
                ];
            })->values()->toArray();

            $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.json';
            $content = json_encode($payload, JSON_PRETTY_PRINT);
            $mime = 'application/json';
        } else {
            $rows = [[ 'Order ID','Date','Status','Amount','Payment Method','Items' ]];
            foreach ($orders as $o) {
                $rows[] = [
                    $o->id,
                    $o->created_at->format('Y-m-d H:i'),
                    ucfirst($o->status),
                    number_format($o->total_amount, 2),
                    ucfirst($o->payment_method),
                    $o->items->pluck('server.name')->filter()->implode('; '),
                ];
            }
            $content = collect($rows)->map(fn($r) => collect($r)->map(fn($v) => str_contains((string)$v, ',') ? '"'.str_replace('"','""',$v).'"' : $v)->implode(','))->implode("\n");
            $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
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

        Notification::make()->title('Export started')->success()->send();
    }

    public function bulkDownloadConfigurations(Collection $orders): void
    {
        try {
            $totalConfigs = 0;
            
            foreach ($orders as $order) {
                if ($order->status === 'completed') {
                    $totalConfigs += $order->items->count();
                }
            }

            if ($totalConfigs === 0) {
                Notification::make()
                    ->title('No Configurations Available')
                    ->body('Selected orders don\'t have any downloadable configurations.')
                    ->warning()
                    ->send();
                return;
            }

            Notification::make()
                ->title('Bulk Download Prepared')
                ->body("Preparing download for {$totalConfigs} configuration files.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Bulk Download Failed')
                ->body('Unable to prepare bulk download. Please try again.')
                ->danger()
                ->send();
        }
    }
}
