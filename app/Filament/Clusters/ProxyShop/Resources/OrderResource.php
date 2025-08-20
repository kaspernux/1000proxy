<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;
use App\Filament\Clusters\ProxyShop;
use App\Filament\Concerns\HasPerformanceOptimizations;
use App\Models\Order;
use App\Models\Customer;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use BackedEnum;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry;

class OrderResource extends Resource
{
    use HasPerformanceOptimizations;
    protected static ?string $model = Order::class;

    protected static ?string $cluster = ProxyShop::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = '🛒 Orders';

    protected static ?string $pluralModelLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    public static function getLabel(): string
    {
        return 'Orders';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        // Allow admin/manager full access; support_manager & sales_support can view via policies (no create/update)
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager() || $user?->isSalesSupport());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()->schema([
                    Section::make('🛒 Order Information')
                        ->description('Order details and customer information')
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-user')
                                    ->getOptionLabelFromRecordUsing(fn (Customer $record): string =>
                                        "{$record->name} ({$record->email})")
                                    ->helperText('Select the customer for this order'),
                                // Staff assignment is no longer stored on orders; users cannot own orders

                                TextInput::make('grand_amount')
                                    ->label('Total Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->placeholder('0.00')
                                    ->helperText('Total order amount including taxes'),
                            ]),

                            Grid::make(3)->schema([
                                Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => '💵 USD - US Dollar',
                                        'EUR' => '💶 EUR - Euro',
                                        'GBP' => '💷 GBP - British Pound',
                                        'BTC' => '₿ BTC - Bitcoin',
                                        'ETH' => '⟠ ETH - Ethereum',
                                        'USDT' => '₮ USDT - Tether',
                                    ])
                                    ->default('USD')
                                    ->required()
                                    ->prefixIcon('heroicon-o-banknotes')
                                    ->helperText('Order currency'),

                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'stripe' => '💳 Stripe (Card)',
                                        'paypal' => '🅿️ PayPal',
                                        'crypto' => '₿ Cryptocurrency',
                                        'nowpayments' => '💰 NowPayments',
                                        'wallet' => '👛 Customer Wallet',
                                        'bank_transfer' => '🏦 Bank Transfer',
                                        'manual' => '✋ Manual Payment',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-o-credit-card')
                                    ->helperText('Payment processing method'),

                                TextInput::make('payment_invoice_url')
                                    ->label('Invoice URL')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->placeholder('https://...')
                                    ->helperText('Payment gateway invoice URL'),
                            ]),

                            Textarea::make('notes')
                                ->label('Order Notes')
                                ->rows(3)
                                ->maxLength(1000)
                                ->placeholder('Enter any order notes or special instructions')
                                ->helperText('Internal notes for this order'),
                            // Removed legacy manager info: orders are customer-owned only
                        ])->columns(1),

                    Section::make('📊 Order Status')
                        ->description('Payment and processing status')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->options([
                                        'pending' => '🟡 Pending',
                                        'processing' => '🔄 Processing',
                                        'paid' => '✅ Paid',
                                        'failed' => '❌ Failed',
                                        'cancelled' => '🚫 Cancelled',
                                        'refunded' => '↩️ Refunded',
                                        'disputed' => '⚠️ Disputed',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->live()
                                    ->helperText('Current payment status'),

                                Select::make('order_status')
                                    ->label('Order Status')
                                    ->options([
                                        'new' => '🆕 New',
                                        'processing' => '⚙️ Processing',
                                        'completed' => '✅ Completed',
                                        'cancelled' => '🚫 Cancelled',
                                        'dispute' => '⚠️ Dispute',
                                        'refund_requested' => '↩️ Refund Requested',
                                        'refunded' => '💸 Refunded',
                                    ])
                                    ->default('new')
                                    ->required()
                                    ->prefixIcon('heroicon-o-clipboard-document-check')
                                    ->live()
                                    ->helperText('Current order fulfillment status'),
                            ]),
                        ])->columns(1),

                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('📈 Order Statistics')
                        ->schema([
                            Placeholder::make('created_at')
                                ->label('Order Date')
                                ->content(fn (Order $record): string =>
                                    $record->created_at ? $record->created_at->format('M j, Y g:i A') : 'Not set')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('updated_at')
                                ->label('Last Updated')
                                ->content(fn (Order $record): string =>
                                    $record->updated_at ? $record->updated_at->diffForHumans() : 'Never')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('items_count')
                                ->label('Items Count')
                                ->content(fn (Order $record): string =>
                                    $record->items()->count() . ' items')
                                ->extraAttributes(['class' => 'text-sm']),

                            Placeholder::make('customer_orders_count')
                                ->label('Customer Total Orders')
                                ->content(fn (Order $record): string =>
                                    $record->customer ? $record->customer->orders()->count() . ' orders' : 'N/A')
                                ->extraAttributes(['class' => 'text-sm']),
                        ]),

                    Section::make('🎯 Quick Actions')
                        ->schema([
                            Placeholder::make('actions_info')
                                ->content('Use the action buttons above to:')
                                ->extraAttributes(['class' => 'text-sm text-gray-600']),

                            Placeholder::make('actions_list')
                                ->content('• Mark as paid/completed<br>• Send notifications<br>• Process refunds<br>• View order items')
                                ->extraAttributes(['class' => 'text-xs text-gray-500'])
                        ])
                        ->hidden(fn (?Order $record) => $record === null),
                ])->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('id')
                    ->label('🆔 Order ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Order ID copied')
                    ->prefix('#')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('customer.name')
                    ->label('👤 Customer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->formatStateUsing(fn (Order $record): string =>
                        $record->customer ? "{$record->customer->name}" : 'Unknown')
                    ->description(fn (Order $record): string =>
                        $record->customer ? $record->customer->email : '')
                    ->color('info'),

                TextColumn::make('grand_amount')
                    ->label('💰 Amount')
                    ->money()
                    ->sortable()
                    ->icon('heroicon-o-currency-dollar')
                    ->formatStateUsing(fn (Order $record): string =>
                        $record->currency . ' ' . number_format($record->grand_amount, 2))
                    ->weight('bold')
                    ->color('success')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->label('Total')->money(),
                        Tables\Columns\Summarizers\Average::make()->label('Avg')->money(),
                    ]),

                BadgeColumn::make('payment_status')
                    ->label('💳 Payment')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'paid',
                        'danger' => ['failed', 'cancelled', 'disputed'],
                        'gray' => 'refunded',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-arrow-path' => 'processing',
                        'heroicon-o-check-circle' => 'paid',
                        'heroicon-o-x-circle' => ['failed', 'cancelled'],
                        'heroicon-o-exclamation-triangle' => 'disputed',
                        'heroicon-o-arrow-uturn-left' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => '🟡 Pending',
                        'processing' => '🔄 Processing',
                        'paid' => '✅ Paid',
                        'failed' => '❌ Failed',
                        'cancelled' => '🚫 Cancelled',
                        'refunded' => '↩️ Refunded',
                        'disputed' => '⚠️ Disputed',
                        default => ucfirst($state)
                    }),

                BadgeColumn::make('order_status')
                    ->label('📦 Order')
                    ->colors([
                        'info' => 'new',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => ['cancelled', 'dispute'],
                        'gray' => ['refund_requested', 'refunded'],
                    ])
                    ->icons([
                        'heroicon-o-sparkles' => 'new',
                        'heroicon-o-cog-6-tooth' => 'processing',
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-x-circle' => 'cancelled',
                        'heroicon-o-exclamation-triangle' => 'dispute',
                        'heroicon-o-arrow-uturn-left' => ['refund_requested', 'refunded'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'new' => '🆕 New',
                        'processing' => '⚙️ Processing',
                        'completed' => '✅ Completed',
                        'cancelled' => '🚫 Cancelled',
                        'dispute' => '⚠️ Dispute',
                        'refund_requested' => '↩️ Refund Requested',
                        'refunded' => '💸 Refunded',
                        default => ucfirst($state)
                    }),

                TextColumn::make('payment_method')
                    ->label('💳 Method')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'stripe' => '💳 Stripe',
                        'paypal' => '🅿️ PayPal',
                        'crypto' => '₿ Crypto',
                        'nowpayments' => '💰 NowPayments',
                        'wallet' => '👛 Wallet',
                        'bank_transfer' => '🏦 Bank',
                        'manual' => '✋ Manual',
                        default => ucfirst($state)
                    })
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('📦 Items')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('📅 Order Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn (Order $record): string =>
                        $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->toggleable()
                    ->summarize([
                        Tables\Columns\Summarizers\Count::make()->label('Count'),
                    ]),

                TextColumn::make('updated_at')
                    ->label('🔄 Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => '🟡 Pending',
                        'processing' => '🔄 Processing',
                        'paid' => '✅ Paid',
                        'failed' => '❌ Failed',
                        'cancelled' => '🚫 Cancelled',
                        'refunded' => '↩️ Refunded',
                        'disputed' => '⚠️ Disputed',
                    ])
                    ->multiple(),

                SelectFilter::make('order_status')
                    ->label('Order Status')
                    ->options([
                        'new' => '🆕 New',
                        'processing' => '⚙️ Processing',
                        'completed' => '✅ Completed',
                        'cancelled' => '🚫 Cancelled',
                        'dispute' => '⚠️ Dispute',
                        'refund_requested' => '↩️ Refund Requested',
                        'refunded' => '💸 Refunded',
                    ])
                    ->multiple(),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'stripe' => '💳 Stripe',
                        'paypal' => '🅿️ PayPal',
                        'crypto' => '₿ Crypto',
                        'nowpayments' => '💰 NowPayments',
                        'wallet' => '👛 Wallet',
                        'bank_transfer' => '🏦 Bank',
                        'manual' => '✋ Manual',
                    ])
                    ->multiple(),

                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options([
                        'USD' => '💵 USD',
                        'EUR' => '💶 EUR',
                        'GBP' => '💷 GBP',
                        'BTC' => '₿ BTC',
                        'ETH' => '⟠ ETH',
                        'USDT' => '₮ USDT',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Order Date From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Order Date Until'),
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
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),

                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->update([
                            'payment_status' => 'paid',
                            'order_status' => 'processing'
                        ]);

                        Notification::make()
                            ->title('Order marked as paid')
                            ->body("Order #{$record->id} has been marked as paid and is now processing.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Order $record): bool =>
                        $record->payment_status !== 'paid'),

                Action::make('mark_completed')
                    ->label('Complete Order')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        $record->markAsCompleted();

                        Notification::make()
                            ->title('Order completed')
                            ->body("Order #{$record->id} has been marked as completed.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Order $record): bool =>
                        $record->order_status !== 'completed'),

                Action::make('view_items')
                    ->label('View Items')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->url(fn (Order $record): string =>
                        route('filament.admin.proxy-shop.resources.order-items.index', ['order' => $record->id])),
                // Removed legacy manager assignment action; users do not own or get assigned to orders
            ])
            // Header column toggle is not available in current Filament version; removed for compatibility
            ->bulkActions([
                // Keep export_csv outside of the BulkActionGroup so tests can capture the StreamedResponse
                \Filament\Actions\BulkAction::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn () => auth()->user()?->can('export', Order::class))
                    ->action(function (Collection $records) {
                        $filename = 'orders_export_' . now()->format('Ymd_His') . '.csv';
                        return response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['ID', 'Customer', 'Amount', 'Currency', 'Payment Method', 'Payment Status', 'Order Status', 'Created At']);
                            foreach ($records as $order) {
                                fputcsv($out, [
                                    $order->id,
                                    optional($order->customer)->name,
                                    $order->grand_amount,
                                    $order->currency,
                                    $order->payment_method,
                                    $order->payment_status,
                                    $order->order_status,
                                    optional($order->created_at)?->toDateTimeString(),
                                ]);
                            }
                            fclose($out);
                        }, $filename, ['Content-Type' => 'text/csv']);
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    \Filament\Actions\BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Order $record) =>
                                $record->update([
                                    'payment_status' => 'paid',
                                    'order_status' => 'processing'
                                ])
                            );

                            Notification::make()
                                ->title('Orders updated')
                                ->body("{$records->count()} orders have been marked as paid.")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Order $record) => $record->markAsCompleted());

                            Notification::make()
                                ->title('Orders completed')
                                ->body("{$records->count()} orders have been marked as completed.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        return self::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-shopping-cart',
                'heading' => 'No orders found',
                'description' => 'Try adjusting filters or date range.',
            ],
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('order_status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('order_status', 'new')->count() > 0 ? 'warning' : null;
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['customer']);
    }

    public static function getGlobalSearchAttributes(): array
    {
        return ['id', 'customer.name', 'customer.email', 'payment_invoice_url'];
    }

    // Infolist for View page
    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            InfolistSection::make('Order Overview')
                ->schema([
                    InfolistGrid::make(3)->schema([
                        TextEntry::make('id')->label('Order ID')->badge()->color('primary')->prefix('#'),
                        TextEntry::make('customer.name')->label('Customer')->icon('heroicon-o-user'),
                        TextEntry::make('created_at')->label('Order Date')->dateTime('M j, Y g:i A')->since(),
                    ]),
                ])->columns(1),

            InfolistSection::make('Payment & Status')
                ->schema([
                    InfolistGrid::make(3)->schema([
                        TextEntry::make('grand_amount')->label('Amount')->money(fn($record) => $record->currency)->icon('heroicon-o-currency-dollar')->color('success'),
                        TextEntry::make('payment_method')->label('Method')->badge(),
                        TextEntry::make('payment_status')->label('Payment')->badge(),
                        TextEntry::make('order_status')->label('Order')->badge(),
                    ]),
                ])->columns(1),

            InfolistSection::make('Links')
                ->schema([
                    TextEntry::make('payment_invoice_url')->label('Invoice URL')->url()->copyable(),
                ])->columns(1),
        ]);
    }
}
