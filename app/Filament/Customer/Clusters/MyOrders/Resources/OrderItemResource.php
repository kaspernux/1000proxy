<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;
use App\Models\OrderItem;
use App\Models\ServerClient;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $cluster = MyOrders::class;
    protected static ?string $navigationLabel = 'Purchased Items';
    protected static ?string $pluralLabel = 'Purchased Items';
    protected static ?string $label = 'Purchased Item';
    protected static ?int $navigationSort = 3;

    // Security: Disable create, edit, delete operations
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['order', 'serverPlan'])
            ->whereHas('order', function ($query) {
                $query->where('customer_id', Auth::guard('customer')->id())
                      ->where('payment_status', 'paid')
                      ->where('order_status', 'completed');
            });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('info')
                ->content('Order items are created automatically when you purchase services. You cannot create or edit them manually.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.id')
                    ->label('Order #')
                    ->copyable()
                    ->sortable()
                    ->prefix('#')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->url(fn (OrderItem $record): string =>
                        route('filament.customer.resources.my-orders.orders.view', ['record' => $record->order_id])
                    ),

                TextColumn::make('serverPlan.name')
                    ->label('Plan Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('info')
                    ->description(fn (OrderItem $record): string =>
                        $record->serverPlan->description ?? ''
                    ),

                TextColumn::make('serverPlan.server.name')
                    ->label('Server')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('unit_amount')
                    ->label('Unit Price')
                    ->money('usd')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('usd')
                    ->sortable()
                    ->color('success')
                    ->weight(FontWeight::SemiBold),

                BadgeColumn::make('provisioning_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'provisioning',
                        'success' => 'active',
                        'danger' => 'failed',
                        'gray' => 'suspended',
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'pending',
                        'heroicon-m-arrow-path' => 'provisioning',
                        'heroicon-m-check-circle' => 'active',
                        'heroicon-m-x-circle' => 'failed',
                        'heroicon-m-pause-circle' => 'suspended',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Purchased')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('server_plan_id')
                    ->label('Plan')
                    ->relationship('serverPlan', 'name')
                    ->searchable()
                    ->indicator('Plan'),

                SelectFilter::make('provisioning_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'provisioning' => 'Provisioning',
                        'active' => 'Active',
                        'failed' => 'Failed',
                        'suspended' => 'Suspended',
                    ])
                    ->indicator('Status'),

                Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Amount From')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Amount To')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount * 100),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount * 100),
                            );
                    })
                    ->indicator('Amount Range'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary'),

                    Action::make('download_config')
                        ->label('Download Config')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (OrderItem $record): bool =>
                            $record->provisioning_status === 'active'
                        )
                        ->action(function (OrderItem $record) {
                            // Configuration download logic
                            $client = ServerClient::where('plan_id', $record->server_plan_id)
                                ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                ->first();

                            if (!$client) {
                                Notification::make()
                                    ->title('Configuration Not Found')
                                    ->body('No configuration found for this item.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            try {
                                $config = $client->config_data ?? $client->client_link;
                                return response()->streamDownload(
                                    fn () => print($config),
                                    "config-{$record->id}.txt"
                                );
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Download Failed')
                                    ->body('Could not download configuration. Please try again later.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('view_order')
                        ->label('View Order')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->url(fn (OrderItem $record): string =>
                            route('filament.customer.resources.my-orders.orders.view', ['record' => $record->order_id])
                        ),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                // No bulk actions for security
            ])
            ->emptyStateHeading('No Purchased Items')
            ->emptyStateDescription('Items from your completed orders will appear here.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Item Details')
                    ->persistTab()
                    ->tabs([
                        Tabs\Tab::make('Overview')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Order Item Details')
                                            ->icon('heroicon-o-clipboard-document-list')
                                            ->schema([
                                                TextEntry::make('order.id')
                                                    ->label('Order Number')
                                                    ->copyable()
                                                    ->prefix('#')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('serverPlan.name')
                                                    ->label('Plan Name')
                                                    ->weight('bold')
                                                    ->color('info'),

                                                TextEntry::make('serverPlan.server.name')
                                                    ->label('Server Location')
                                                    ->badge()
                                                    ->color('gray'),

                                                TextEntry::make('quantity')
                                                    ->label('Quantity')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('provisioning_status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'pending' => 'warning',
                                                        'provisioning' => 'info',
                                                        'active' => 'success',
                                                        'failed' => 'danger',
                                                        'suspended' => 'gray',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('created_at')
                                                    ->label('Purchased Date')
                                                    ->since()
                                                    ->color('gray'),
                                            ]),

                                        Section::make('Pricing Information')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->schema([
                                                TextEntry::make('unit_amount')
                                                    ->label('Unit Price')
                                                    ->money('usd')
                                                    ->color('success'),

                                                TextEntry::make('total_amount')
                                                    ->label('Total Amount')
                                                    ->money('usd')
                                                    ->weight('bold')
                                                    ->color('success'),

                                                TextEntry::make('order.total_amount')
                                                    ->label('Order Total')
                                                    ->money('usd')
                                                    ->color('info'),

                                                TextEntry::make('order.status')
                                                    ->label('Order Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'completed' => 'success',
                                                        'pending' => 'warning',
                                                        'failed' => 'danger',
                                                        default => 'gray',
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Configuration')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Client Configuration')
                                    ->icon('heroicon-o-link')
                                    ->description('Download your configuration files or view QR codes for easy setup.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Group::make([
                                                    TextEntry::make('serverClient.client_link')
                                                        ->label('Connection Link')
                                                        ->copyable()
                                                        ->placeholder('Configuration will be available once provisioning is complete')
                                                        ->visible(fn (OrderItem $record): bool =>
                                                            $record->provisioning_status === 'active' &&
                                                            $record->serverClient &&
                                                            $record->serverClient->client_link
                                                        ),

                                                    TextEntry::make('serverClient.email')
                                                        ->label('Client Identifier')
                                                        ->copyable()
                                                        ->placeholder('Identifier will be assigned after provisioning')
                                                        ->visible(fn (OrderItem $record): bool =>
                                                            $record->provisioning_status === 'active' &&
                                                            $record->serverClient
                                                        ),

                                                    TextEntry::make('serverPlan.protocol')
                                                        ->label('Protocol')
                                                        ->badge()
                                                        ->color('info'),

                                                    TextEntry::make('serverPlan.port')
                                                        ->label('Port')
                                                        ->badge()
                                                        ->color('gray'),
                                                ]),

                                                Group::make([
                                                    ImageEntry::make('qr_code')
                                                        ->label('QR Code for Easy Setup')
                                                        ->state(function (OrderItem $record): ?string {
                                                            if ($record->provisioning_status !== 'active' || !$record->serverClient?->client_link) {
                                                                return null;
                                                            }

                                                            try {
                                                                $qrCodeService = app(QrCodeService::class);
                                                                return $qrCodeService->generateClientQrCode(
                                                                    $record->serverClient->client_link,
                                                                    [
                                                                        'colorScheme' => 'primary',
                                                                        'style' => 'dot',
                                                                        'eye' => 'circle'
                                                                    ]
                                                                );
                                                            } catch (\Exception $e) {
                                                                return null;
                                                            }
                                                        })
                                                        ->height(200)
                                                        ->width(200)
                                                        ->visible(fn (OrderItem $record): bool =>
                                                            $record->provisioning_status === 'active' &&
                                                            $record->serverClient?->client_link
                                                        ),

                                                    TextEntry::make('status_message')
                                                        ->label('Configuration Status')
                                                        ->state(function (OrderItem $record): string {
                                                            return match ($record->provisioning_status) {
                                                                'pending' => 'Waiting for provisioning to begin...',
                                                                'provisioning' => 'Setting up your service, please wait...',
                                                                'active' => 'Configuration ready for download',
                                                                'failed' => 'Provisioning failed, please contact support',
                                                                'suspended' => 'Service temporarily suspended',
                                                                default => 'Status unknown',
                                                            };
                                                        })
                                                        ->color(fn (OrderItem $record): string => match ($record->provisioning_status) {
                                                            'pending' => 'warning',
                                                            'provisioning' => 'info',
                                                            'active' => 'success',
                                                            'failed' => 'danger',
                                                            'suspended' => 'gray',
                                                            default => 'gray',
                                                        })
                                                        ->icon(fn (OrderItem $record): string => match ($record->provisioning_status) {
                                                            'pending' => 'heroicon-o-clock',
                                                            'provisioning' => 'heroicon-o-arrow-path',
                                                            'active' => 'heroicon-o-check-circle',
                                                            'failed' => 'heroicon-o-x-circle',
                                                            'suspended' => 'heroicon-o-pause-circle',
                                                            default => 'heroicon-o-question-mark-circle',
                                                        }),
                                                ]),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Technical Details')
                            ->icon('heroicon-o-server')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Plan Specifications')
                                            ->icon('heroicon-o-chart-bar')
                                            ->schema([
                                                TextEntry::make('serverPlan.description')
                                                    ->label('Plan Description')
                                                    ->markdown()
                                                    ->placeholder('No description available'),

                                                TextEntry::make('serverPlan.bandwidth')
                                                    ->label('Bandwidth')
                                                    ->suffix(' GB')
                                                    ->placeholder('Unlimited'),

                                                TextEntry::make('serverPlan.max_connections')
                                                    ->label('Max Connections')
                                                    ->numeric()
                                                    ->placeholder('Unlimited'),

                                                TextEntry::make('serverPlan.features')
                                                    ->label('Features')
                                                    ->listWithLineBreaks()
                                                    ->placeholder('Standard features'),
                                            ]),

                                        Section::make('Server Information')
                                            ->icon('heroicon-o-server')
                                            ->schema([
                                                TextEntry::make('serverPlan.server.location')
                                                    ->label('Server Location')
                                                    ->badge()
                                                    ->color('info'),

                                                TextEntry::make('serverPlan.server.ip_address')
                                                    ->label('Server IP')
                                                    ->copyable()
                                                    ->placeholder('IP will be assigned'),

                                                TextEntry::make('serverPlan.server.status')
                                                    ->label('Server Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'maintenance' => 'warning',
                                                        'offline' => 'danger',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('serverPlan.server.load_percentage')
                                                    ->label('Server Load')
                                                    ->suffix('%')
                                                    ->color(fn (?int $state): string => match (true) {
                                                        $state === null => 'gray',
                                                        $state < 70 => 'success',
                                                        $state < 90 => 'warning',
                                                        default => 'danger',
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Order Information')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Section::make('Related Order Details')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('order.id')
                                                    ->label('Order ID')
                                                    ->copyable()
                                                    ->prefix('#')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('order.status')
                                                    ->label('Order Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'completed' => 'success',
                                                        'pending' => 'warning',
                                                        'failed' => 'danger',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('order.created_at')
                                                    ->label('Order Date')
                                                    ->since()
                                                    ->color('gray'),

                                                TextEntry::make('order.total_amount')
                                                    ->label('Order Total')
                                                    ->money('usd')
                                                    ->color('success'),

                                                TextEntry::make('order.payment_status')
                                                    ->label('Payment Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'paid' => 'success',
                                                        'pending' => 'warning',
                                                        'failed' => 'danger',
                                                        'refunded' => 'gray',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('order.payment_method')
                                                    ->label('Payment Method')
                                                    ->badge()
                                                    ->color('info'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        if (!$customerId) {
            return null;
        }

        $count = static::getModel()::query()
            ->whereHas('order', function (Builder $query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderItems::route('/'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
        ];
    }
}