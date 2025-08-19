<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\RelationManagers;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Models\OrderServerClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use BackedEnum;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $cluster = ProxyShop::class;
    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        // Allow support roles to view order items; mutations controlled by policies
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager() || $user?->isSalesSupport());
    }

    public static function getLabel(): string
    {
        return 'Order Items';
    }

    public static function getPluralLabel(): string
    {
        return 'Order Items';
    }

    // Navigation badge for items requiring attention
    public static function getNavigationBadge(): ?string
    {
        $incompleteCount = static::getModel()::whereHas('order', function ($query) {
            $query->where('order_status', '!=', 'completed');
        })->count();

        return $incompleteCount > 0 ? (string) $incompleteCount : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Order Item Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        // Left Column
                                        Group::make([
                                            Fieldset::make('Order Association')
                                                ->schema([
                                                    Select::make('order_id')
                                                        ->label('Parent Order')
                                                        ->relationship('order', 'id')
                                                        ->searchable()
                                                        ->preload()
                                                        ->required()
                                                        ->getSearchResultsUsing(function (string $search): array {
                                                            return Order::where('id', 'like', "%{$search}%")
                                                                ->orWhereHas('customer', function ($query) use ($search) {
                                                                    $query->where('name', 'like', "%{$search}%");
                                                                })
                                                                ->limit(50)
                                                                ->get()
                                                                ->mapWithKeys(function ($order) {
                                                                    return [$order->id => "Order #{$order->id} - {$order->customer->name}"];
                                                                })
                                                                ->toArray();
                                                        })
                                                        ->getOptionLabelUsing(function ($value): ?string {
                                                            $order = Order::find($value);
                                                            return $order ? "Order #{$order->id} - {$order->customer->name}" : null;
                                                        }),

                                                    Select::make('server_plan_id')
                                                        ->label('Server Plan')
                                                        ->relationship('serverPlan', 'name')
                                                        ->searchable()
                                                        ->preload()
                                                        ->required()
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            $serverPlan = ServerPlan::find($state);
                                                            if ($serverPlan) {
                                                                $unitAmount = (float) $serverPlan->price;
                                                                $quantity = (int) ($get('quantity') ?? 1);
                                                                $set('unit_amount', $unitAmount);
                                                                $set('total_amount', $unitAmount * $quantity);
                                                            }
                                                        }),

                                                    Placeholder::make('server_plan_details')
                                                        ->label('Plan Details')
                                                        ->content(function ($get) {
                                                            $planId = $get('server_plan_id');
                                                            if (!$planId) return 'Select a plan to see details';

                                                            $plan = ServerPlan::find($planId);
                                                            if (!$plan) return 'Plan not found';
                                                                              return "Server: " . $plan->server->name . " | " .
                                                   "Price: $" . $plan->price . " | " .
                                                   "Traffic: " . $plan->traffic_limit . "GB";
                                                        }),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),

                                        // Right Column
                                        Group::make([
                                            Fieldset::make('Quantity & Pricing')
                                                ->schema([
                                                    TextInput::make('quantity')
                                                        ->label('Quantity')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(1)
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            $quantity = (int) $state;
                                                            $unitAmount = (float) $get('unit_amount');
                                                            $set('total_amount', $quantity * $unitAmount);
                                                        }),

                                                    TextInput::make('unit_amount')
                                                        ->label('Unit Price')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->required()
                                                        ->default(0)
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            $quantity = (int) $get('quantity');
                                                            $unitAmount = (float) $state;
                                                            $set('total_amount', $quantity * $unitAmount);
                                                        }),

                                                    TextInput::make('total_amount')
                                                        ->label('Total Amount')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->required()
                                                        ->default(0)
                                                        ->disabled(),

                                                    Toggle::make('agent_bought')
                                                        ->label('Agent Purchase')
                                                        ->helperText('Was this purchased by an agent?')
                                                        ->default(false),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),
                                    ]),
                            ]),

                        Tabs\Tab::make('Provisioning Status')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Fieldset::make('Provisioning Information')
                                    ->schema([
                                        Placeholder::make('provisioning_summary')
                                            ->label('Provisioning Summary')
                                            ->content(function ($record) {
                                                if (!$record) return 'No data available';

                                                $status = $record->getProvisioningStatus();
                                                return "Requested: " . $record->quantity . " | " .
                                                       "Provisioned: " . $status['total_provisions'] . " | " .
                                                       "Completed: " . $status['completed'] . " | " .
                                                       "Failed: " . $status['failed'];
                                            }),

                                        Placeholder::make('qr_codes_available')
                                            ->label('QR Codes Status')
                                            ->content(function ($record) {
                                                if (!$record) return 'No data available';

                                                $qrCodes = $record->getQrCodes();
                                                $available = collect($qrCodes)->filter()->count();
                                                return "Available QR codes: " . $available;
                                            }),

                                        Placeholder::make('clients_created')
                                            ->label('Server Clients')
                                            ->content(function ($record) {
                                                if (!$record) return 'No data available';

                                                $clients = $record->getClients();
                                                return "Server clients created: " . $clients->count();
                                            }),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('order.id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->url(fn (OrderItem $record): string =>
                        \App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages\ViewOrder::getUrl([$record->order])
                    )
                    ->color('primary'),

                TextColumn::make('order.customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->tooltip(fn (OrderItem $record): string => $record->order->customer->email ?? ''),

                TextColumn::make('serverPlan.name')
                    ->label('Server Plan')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('serverPlan.server.name')
                    ->label('Server')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('unit_amount')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                IconColumn::make('agent_bought')
                    ->label('Agent')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip('Purchased by agent'),

                BadgeColumn::make('order.order_status')
                    ->label('Order Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'dispute',
                        'gray' => 'cancelled',
                    ]),

                TextColumn::make('provisioning_status')
                    ->label('Provisioning')
                    ->formatStateUsing(function (OrderItem $record): string {
                        $status = $record->getProvisioningStatus();
                        return $status['completed'] . "/" . $record->quantity;
                    })
                    ->badge()
                    ->color(function (OrderItem $record): string {
                        $status = $record->getProvisioningStatus();
                        if ($status['failed'] > 0) return 'danger';
                        if ($record->isFullyProvisioned()) return 'success';
                        return 'warning';
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'id')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('server_plan_id')
                    ->label('Server Plan')
                    ->relationship('serverPlan', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('agent_bought')
                    ->label('Agent Purchase')
                    ->placeholder('All items')
                    ->trueLabel('Agent purchases only')
                    ->falseLabel('Customer purchases only'),

                SelectFilter::make('order.order_status')
                    ->label('Order Status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'dispute' => 'Dispute',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('view_qr_codes')
                        ->label('View QR Codes')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->action(function (OrderItem $record) {
                            $qrCodes = $record->getQrCodes();

                            if (empty(array_filter($qrCodes))) {
                                Notification::make()
                                    ->title('No QR codes available')
                                    ->body('This order item has no QR codes generated yet')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            Notification::make()
                                ->title('QR codes available')
                                ->body('Found QR codes for this order item')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_clients')
                        ->label('View Clients')
                        ->icon('heroicon-o-users')
                        ->color('primary')
                        ->action(function (OrderItem $record) {
                            $clients = $record->getClients();

                            Notification::make()
                                ->title('Server clients')
                                ->body("Found " . $clients->count() . " server clients for this item")
                                ->success()
                                ->send();
                        }),

                    Action::make('calculate_with_fees')
                        ->label('Total with Fees')
                        ->icon('heroicon-o-calculator')
                        ->color('gray')
                        ->action(function (OrderItem $record) {
                            $totalWithFees = $record->getTotalAmountWithFees();

                            Notification::make()
                                ->title('Total with fees')
                                ->body("Total amount including setup fees: $" . number_format($totalWithFees, 2))
                                ->info()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    BulkAction::make('mark_agent_bought')
                        ->label('Mark as Agent Purchase')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['agent_bought' => true]);

                            Notification::make()
                                ->title('Items updated')
                                ->body($records->count() . ' items marked as agent purchases')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('recalculate_totals')
                        ->label('Recalculate Totals')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'total_amount' => $record->unit_amount * $record->quantity
                                ]);
                            });

                            Notification::make()
                                ->title('Totals recalculated')
                                ->body($records->count() . ' item totals have been recalculated')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No order items yet')
            ->emptyStateDescription('Order items will appear here when orders are created.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->striped()
            ->recordAction('view')
            ->recordUrl(
                fn (OrderItem $record): string => Pages\ViewOrderItem::getUrl([$record]),
            );
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Infolists\Components\Tabs::make('Order Item Details')->tabs([
                    \Filament\Infolists\Components\Tabs\Tab::make('Overview')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            \Filament\Infolists\Components\Section::make('Item Information')
                                ->columns(2)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('id')
                                        ->label('Item ID'),
                                    \Filament\Infolists\Components\TextEntry::make('order.id')
                                        ->label('Order #')
                                        ->url(fn (OrderItem $record): string =>
                                            \App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages\ViewOrder::getUrl([$record->order])
                                        ),
                                    \Filament\Infolists\Components\TextEntry::make('order.customer.name')
                                        ->label('Customer'),
                                    \Filament\Infolists\Components\TextEntry::make('serverPlan.name')
                                        ->label('Server Plan'),
                                    \Filament\Infolists\Components\TextEntry::make('serverPlan.server.name')
                                        ->label('Server')
                                        ->badge()
                                        ->color('info'),
                                    \Filament\Infolists\Components\TextEntry::make('quantity')
                                        ->label('Quantity')
                                        ->badge()
                                        ->color('primary'),
                                    \Filament\Infolists\Components\TextEntry::make('unit_amount')
                                        ->label('Unit Price')
                                        ->money('USD'),
                                    \Filament\Infolists\Components\TextEntry::make('total_amount')
                                        ->label('Total Amount')
                                        ->money('USD'),
                                    \Filament\Infolists\Components\TextEntry::make('agent_bought')
                                        ->label('Agent Purchase')
                                        ->badge()
                                        ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                                    \Filament\Infolists\Components\TextEntry::make('created_at')
                                        ->label('Created')
                                        ->since(),
                                ]),
                        ]),

                    \Filament\Infolists\Components\Tabs\Tab::make('Provisioning')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            \Filament\Infolists\Components\Section::make('Provisioning Status')
                                ->columns(2)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('provisioning_summary')
                                        ->label('Status Summary')
                                        ->formatStateUsing(function (OrderItem $record): string {
                                            $status = $record->getProvisioningStatus();
                                            return "Requested: " . $record->quantity . " | " .
                                                   "Completed: " . $status['completed'] . " | " .
                                                   "Failed: " . $status['failed'] . " | " .
                                                   "Pending: " . $status['pending'];
                                        })
                                        ->columnSpanFull(),

                                    \Filament\Infolists\Components\TextEntry::make('is_fully_provisioned')
                                        ->label('Fully Provisioned')
                                        ->formatStateUsing(fn (OrderItem $record): string =>
                                            $record->isFullyProvisioned() ? 'Yes' : 'No'
                                        )
                                        ->badge()
                                        ->color(fn (OrderItem $record): string =>
                                            $record->isFullyProvisioned() ? 'success' : 'warning'
                                        ),

                                    \Filament\Infolists\Components\TextEntry::make('total_with_fees')
                                        ->label('Total with Setup Fees')
                                        ->formatStateUsing(fn (OrderItem $record): string =>
                                            '$' . number_format($record->getTotalAmountWithFees(), 2)
                                        ),
                                ]),
                        ]),

                    \Filament\Infolists\Components\Tabs\Tab::make('QR Codes')
                        ->icon('heroicon-o-qr-code')
                        ->schema([
                            \Filament\Infolists\Components\Section::make('Available QR Codes')
                                ->columns(1)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('qr_codes_summary')
                                        ->label('QR Codes Status')
                                        ->formatStateUsing(function (OrderItem $record): string {
                                            $qrCodes = $record->getQrCodes();
                                            $available = collect($qrCodes)->filter()->count();
                                            $total = count($qrCodes);
                                            return "Available: " . $available . "/" . $total;
                                        })
                                        ->badge()
                                        ->color(function (OrderItem $record): string {
                                            $qrCodes = $record->getQrCodes();
                                            $available = collect($qrCodes)->filter()->count();
                                            return $available > 0 ? 'success' : 'gray';
                                        }),
                                ]),
                        ]),
                ])->columnSpanFull(),
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
            'index' => Pages\ListOrderItems::route('/'),
            'create' => Pages\CreateOrderItem::route('/create'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
            'edit' => Pages\EditOrderItem::route('/{record}/edit'),
        ];
    }
}
