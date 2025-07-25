<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerClient;
use App\Services\QrCodeService;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;

class OrderManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'My Orders';
    protected static string $view = 'filament.customer.pages.order-management';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getOrdersQuery())
            ->columns([
                TextColumn::make('id')
                    ->label('Order #')
                    ->prefix('#')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->description(fn (Order $record): string => $record->created_at->diffForHumans()),

                TextColumn::make('items_summary')
                    ->label('Items')
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
                    ->label('Total')
                    ->money('USD')
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->sortable(),

                IconColumn::make('status')
                    ->label('Status')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'processing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        'refunded' => 'heroicon-o-arrow-uturn-left',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(fn (string $state): string => ucfirst($state)),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wallet' => 'success',
                        'bitcoin' => 'warning',
                        'monero' => 'info',
                        'solana' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->formatStateUsing(function (Order $record): ?string {
                        // Get the earliest expiry from server clients
                        $earliestExpiry = $record->items
                            ->map(fn ($item) => $item->server_client?->expiry_time)
                            ->filter()
                            ->sort()
                            ->first();

                        return $earliestExpiry ? $earliestExpiry->format('M j, Y') : null;
                    })
                    ->description(function (Order $record): ?string {
                        $earliestExpiry = $record->items
                            ->map(fn ($item) => $item->server_client?->expiry_time)
                            ->filter()
                            ->sort()
                            ->first();

                        return $earliestExpiry ? $earliestExpiry->diffForHumans() : null;
                    })
                    ->color(function (Order $record): string {
                        $earliestExpiry = $record->items
                            ->map(fn ($item) => $item->server_client?->expiry_time)
                            ->filter()
                            ->sort()
                            ->first();

                        if (!$earliestExpiry) return 'gray';

                        $daysUntilExpiry = now()->diffInDays($earliestExpiry, false);

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
                            $expiry = $item->server_client?->expiry_time;
                            return $expiry && now()->diffInDays($expiry, false) <= 30;
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s'); // Real-time updates every 60 seconds
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
            ->with(['items.server', 'items.server_client'])
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
                    // Extend expiry by one month
                    $item->server_client->update([
                        'expiry_time' => $item->server_client->expiry_time->addMonth()
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
}
