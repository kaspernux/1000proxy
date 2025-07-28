<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\Pages;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServerClient;
use App\Models\OrderServerClient;
use App\Services\QrCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\View;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Carbon\Carbon;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $cluster = MyOrders::class;
    protected static ?string $navigationLabel = 'My Orders';
    protected static ?string $pluralLabel = 'My Orders';
    protected static ?string $label = 'Order';
    protected static ?int $navigationSort = 1;

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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('info')
                ->content('You cannot create orders manually. Orders are generated automatically when you checkout.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->copyable()
                    ->sortable()
                    ->searchable()
                    ->prefix('#')
                    ->color('primary')
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('grand_amount')
                    ->label('Total Amount')
                    ->money('usd')
                    ->sortable()
                    ->color('success')
                    ->weight(FontWeight::SemiBold),

                BadgeColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->colors([
                        'primary' => 'USD',
                        'success' => 'EUR',
                        'warning' => 'GBP',
                    ]),

                BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'gray' => fn ($state): bool => !in_array($state, ['pending', 'paid', 'failed']),
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'pending',
                        'heroicon-m-check-circle' => 'paid',
                        'heroicon-m-x-circle' => 'failed',
                    ])
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('order_status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'new',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'dispute',
                    ])
                    ->icons([
                        'heroicon-m-plus-circle' => 'new',
                        'heroicon-m-arrow-path' => 'processing',
                        'heroicon-m-check-badge' => 'completed',
                        'heroicon-m-exclamation-triangle' => 'dispute',
                    ])
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Stripe' => 'gray',
                        'NowPayments' => 'info',
                        'Wallet' => 'success',
                        'PayPal' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Stripe' => 'heroicon-m-credit-card',
                        'NowPayments' => 'heroicon-m-currency-dollar',
                        'Wallet' => 'heroicon-m-wallet',
                        'PayPal' => 'heroicon-m-banknotes',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->description(fn (Order $record): string => $record->created_at->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->indicator('Payment Status'),

                SelectFilter::make('order_status')
                    ->label('Order Status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'dispute' => 'Dispute',
                    ])
                    ->indicator('Order Status'),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Created From'),
                        DatePicker::make('created_until')->label('Created Until'),
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
                    ->indicator('Order Date Range'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary'),

                    Action::make('download_invoice')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->color('success')
                        ->visible(fn (Order $record): bool => $record->invoice !== null)
                        ->action(function (Order $record) {
                            $invoice = $record->invoice;

                            if (!$invoice) {
                                Notification::make()
                                    ->title('No Invoice Found')
                                    ->body('This order does not have an invoice yet.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            try {
                                $pdf = Pdf::loadView('pdf.invoice', [
                                    'invoice' => $invoice,
                                    'order' => $record,
                                    'customer' => $record->customer,
                                ]);

                                return response()->streamDownload(
                                    fn () => print($pdf->stream()),
                                    "Invoice-{$invoice->id}.pdf"
                                );
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Download Failed')
                                    ->body('Could not generate the invoice PDF. Please try again later.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('download_configs')
                        ->label('Download Configs')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (Order $record): bool => 
                            $record->order_status === 'completed' && 
                            $record->payment_status === 'paid' &&
                            $record->getAllClients()->isNotEmpty()
                        )
                        ->action(function (Order $record) {
                            $configs = $record->getClientConfigurations();

                            if (empty($configs)) {
                                Notification::make()
                                    ->title('No Configurations Available')
                                    ->body('Your order configurations are not ready yet.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Create a temporary file with all configurations
                            $content = "# Order #{$record->id} Client Configurations\n\n";
                            foreach ($configs as $index => $config) {
                                $content .= "## Client " . ($index + 1) . "\n";
                                $content .= "Configuration Link: {$config['link']}\n\n";
                                $content .= "QR Code: {$config['qr_code']}\n\n";
                                $content .= "---\n\n";
                            }

                            return response()->streamDownload(
                                function () use ($content) {
                                    echo $content;
                                },
                                "Order-{$record->id}-Configurations.txt",
                                ['Content-Type' => 'text/plain']
                            );
                        }),

                    Action::make('reorder')
                        ->label('Reorder')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Order $record): bool => $record->order_status === 'completed')
                        ->action(function (Order $record) {
                            // This would redirect to checkout with the same items
                            Notification::make()
                                ->title('Reorder Feature')
                                ->body('This feature will be available soon.')
                                ->info()
                                ->send();
                        }),
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
            ->emptyStateHeading('No Orders Yet')
            ->emptyStateDescription('You haven\'t placed any orders yet. Start shopping to see your orders here!')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Order Details')
                    ->persistTab()
                    ->columnSpanFull()
                    ->tabs([
                        // Order Overview Tab
                        Tabs\Tab::make('Overview')
                            ->icon('heroicon-m-shopping-bag')
                            ->badge(fn (Order $record): string => "#{$record->id}")
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Order Information')
                                            ->description('Basic order details and status')
                                            ->icon('heroicon-m-information-circle')
                                            ->schema([
                                                TextEntry::make('id')
                                                    ->label('Order Number')
                                                    ->formatStateUsing(fn ($state): string => "#{$state}")
                                                    ->copyable()
                                                    ->color('primary')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('created_at')
                                                    ->label('Order Date')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->since()
                                                    ->color('gray'),

                                                TextEntry::make('updated_at')
                                                    ->label('Last Updated')
                                                    ->since()
                                                    ->color('gray'),
                                            ]),

                                        Section::make('Payment Details')
                                            ->description('Payment and billing information')
                                            ->icon('heroicon-m-credit-card')
                                            ->schema([
                                                TextEntry::make('grand_amount')
                                                    ->label('Total Amount')
                                                    ->money('usd')
                                                    ->color('success')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('currency')
                                                    ->label('Currency')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('paymentMethod.name')
                                                    ->label('Payment Method')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'Stripe' => 'gray',
                                                        'NowPayments' => 'info',
                                                        'Wallet' => 'success',
                                                        'PayPal' => 'primary',
                                                        default => 'gray',
                                                    }),
                                            ]),

                                        Section::make('Status Information')
                                            ->description('Current order and payment status')
                                            ->icon('heroicon-m-check-badge')
                                            ->schema([
                                                TextEntry::make('payment_status')
                                                    ->label('Payment Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'pending' => 'warning',
                                                        'paid' => 'success',
                                                        'failed' => 'danger',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('order_status')
                                                    ->label('Order Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'new' => 'gray',
                                                        'processing' => 'warning',
                                                        'completed' => 'success',
                                                        'dispute' => 'danger',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('provision_summary')
                                                    ->label('Provisioning Status')
                                                    ->formatStateUsing(function (Order $record): string {
                                                        $status = $record->getProvisioningStatus();
                                                        if ($status['total'] === 0) {
                                                            return 'No items to provision';
                                                        }
                                                        return "{$status['completed']}/{$status['total']} completed";
                                                    })
                                                    ->badge()
                                                    ->color(function (Order $record): string {
                                                        $status = $record->getProvisioningStatus();
                                                        if ($status['total'] === 0) return 'gray';
                                                        if ($status['failed'] > 0) return 'danger';
                                                        if ($status['completed'] === $status['total']) return 'success';
                                                        return 'warning';
                                                    }),
                                            ]),
                                    ]),

                                Section::make('Order Notes')
                                    ->description('Additional information and special instructions')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        TextEntry::make('notes')
                                            ->label('Notes')
                                            ->markdown()
                                            ->placeholder('No special notes for this order'),
                                    ])
                                    ->visible(fn (Order $record): bool => !empty($record->notes)),
                            ]),

                        // Order Items Tab
                        Tabs\Tab::make('Items')
                            ->icon('heroicon-m-cube')
                            ->badge(fn (Order $record): string => $record->items->count())
                            ->schema([
                                Section::make('Order Items')
                                    ->description('Detailed breakdown of all items in this order')
                                    ->icon('heroicon-m-list-bullet')
                                    ->schema([
                                        RepeatableEntry::make('items')
                                            ->schema([
                                                Grid::make(4)
                                                    ->schema([
                                                        TextEntry::make('serverPlan.name')
                                                            ->label('Plan Name')
                                                            ->weight(FontWeight::SemiBold)
                                                            ->color('primary'),

                                                        TextEntry::make('quantity')
                                                            ->label('Quantity')
                                                            ->badge()
                                                            ->color('info'),

                                                        TextEntry::make('unit_amount')
                                                            ->label('Unit Price')
                                                            ->money('usd')
                                                            ->color('success'),

                                                        TextEntry::make('total_amount')
                                                            ->label('Total')
                                                            ->money('usd')
                                                            ->weight(FontWeight::Bold)
                                                            ->color('success'),
                                                    ]),

                                                TextEntry::make('serverPlan.description')
                                                    ->label('Plan Description')
                                                    ->markdown()
                                                    ->color('gray'),

                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('serverPlan.server.name')
                                                            ->label('Server')
                                                            ->badge()
                                                            ->color('primary'),

                                                        TextEntry::make('serverPlan.server.location')
                                                            ->label('Location')
                                                            ->badge()
                                                            ->color('info'),

                                                        TextEntry::make('serverPlan.server.protocol')
                                                            ->label('Protocol')
                                                            ->badge()
                                                            ->color('warning'),
                                                    ]),
                                            ])
                                            ->contained(false)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Invoice Tab
                        Tabs\Tab::make('Invoice')
                            ->icon('heroicon-m-receipt-percent')
                            ->visible(fn (Order $record): bool => $record->invoice !== null)
                            ->schema([
                                Section::make('Invoice Information')
                                    ->description('View and download your invoice')
                                    ->icon('heroicon-m-document')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('invoice.id')
                                                    ->label('Invoice ID')
                                                    ->copyable()
                                                    ->color('primary'),

                                                TextEntry::make('invoice.price_amount')
                                                    ->label('Amount')
                                                    ->money('usd')
                                                    ->color('success'),

                                                TextEntry::make('invoice.payment_status')
                                                    ->label('Payment Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'success',
                                                        'failed' => 'danger',
                                                        default => 'gray',
                                                    }),
                                            ]),

                                        View::make('filament.infolists.components.invoice-preview')
                                            ->viewData(fn (Order $record) => [
                                                'invoice' => $record->invoice,
                                                'order' => $record,
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->contained(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['invoice', 'customer', 'items.serverPlan.server', 'paymentMethod', 'orderServerClients.client'])
            ->where('customer_id', Auth::guard('customer')->id());
    }

    public static function getTabs(): array
    {
        $customerId = Auth::guard('customer')->id();

        return [
            'all' => Tab::make('All Orders')
                ->icon('heroicon-m-shopping-bag')
                ->badge(Order::where('customer_id', $customerId)->count()),

            'paid' => Tab::make('Paid')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'paid'))
                ->badge(Order::where('customer_id', $customerId)->where('payment_status', 'paid')->count())
                ->badgeColor('success'),

            'pending' => Tab::make('Pending Payment')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'pending'))
                ->badge(Order::where('customer_id', $customerId)->where('payment_status', 'pending')->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->icon('heroicon-m-arrow-path')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'processing'))
                ->badge(Order::where('customer_id', $customerId)->where('order_status', 'processing')->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completed')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'completed'))
                ->badge(Order::where('customer_id', $customerId)->where('order_status', 'completed')->count())
                ->badgeColor('primary'),

            'failed' => Tab::make('Failed')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'failed'))
                ->badge(Order::where('customer_id', $customerId)->where('payment_status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        $pendingCount = Order::where('customer_id', $customerId)
            ->where('payment_status', 'pending')
            ->count();
        
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}

