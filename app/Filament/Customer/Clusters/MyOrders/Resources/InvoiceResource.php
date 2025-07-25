<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource\Pages;
use App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Order;
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
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;


class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $cluster = MyOrders::class;
    protected static ?string $navigationLabel = 'My Invoices';
    protected static ?string $pluralLabel = 'My Invoices';
    protected static ?string $label = 'Invoice';
    protected static ?int $navigationSort = 2;

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
                ->content('Invoices are generated automatically when you place an order. You cannot create or edit invoices manually.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Invoice #')
                    ->copyable()
                    ->sortable()
                    ->searchable()
                    ->prefix('INV-')
                    ->color('primary')
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order #')
                    ->copyable()
                    ->sortable()
                    ->searchable()
                    ->prefix('#')
                    ->color('info')
                    ->url(fn (Invoice $record): string => route('filament.customer.resources.my-orders.orders.view', ['record' => $record->order_id])),

                Tables\Columns\TextColumn::make('price_amount')
                    ->label('Invoice Amount')
                    ->money('usd')
                    ->sortable()
                    ->color('success')
                    ->weight(FontWeight::SemiBold),

                BadgeColumn::make('price_currency')
                    ->label('Currency')
                    ->sortable()
                    ->colors([
                        'primary' => 'USD',
                        'success' => 'EUR',
                        'warning' => 'GBP',
                    ]),

                BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => ['confirmed', 'paid'],
                        'danger' => 'failed',
                        'gray' => fn ($state): bool => !in_array($state, ['pending', 'confirmed', 'paid', 'failed']),
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'pending',
                        'heroicon-m-check-circle' => ['confirmed', 'paid'],
                        'heroicon-m-x-circle' => 'failed',
                    ])
                    ->sortable()
                    ->searchable(),

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

                Tables\Columns\TextColumn::make('amount_received')
                    ->label('Amount Received')
                    ->money('usd')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => $record->amount_received > 0),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Invoice Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->description(fn (Invoice $record): string => $record->created_at->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->indicator('Payment Status'),

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
                                fn (Builder $query, $amount): Builder => $query->where('price_amount', '>=', $amount * 100),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('price_amount', '<=', $amount * 100),
                            );
                    })
                    ->indicator('Amount Range'),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Invoice Date From'),
                        DatePicker::make('created_until')->label('Invoice Date Until'),
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
                    ->indicator('Invoice Date Range'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary'),

                    Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->color('success')
                        ->action(function (Invoice $record) {
                            try {
                                $pdf = Pdf::loadView('pdf.invoice', [
                                    'invoice' => $record,
                                    'order' => $record->order,
                                    'customer' => $record->order->customer,
                                ]);

                                return response()->streamDownload(
                                    fn () => print($pdf->stream()),
                                    "Invoice-{$record->id}.pdf"
                                );
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Download Failed')
                                    ->body('Could not generate the invoice PDF. Please try again later.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('view_order')
                        ->label('View Order')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->url(fn (Invoice $record): string => route('filament.customer.resources.my-orders.orders.view', ['record' => $record->order_id]))
                        ->openUrlInNewTab(),

                    Action::make('payment_link')
                        ->label('Payment Link')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->visible(fn (Invoice $record): bool => 
                            $record->payment_status === 'pending' && 
                            !empty($record->invoice_url)
                        )
                        ->url(fn (Invoice $record): string => $record->invoice_url)
                        ->openUrlInNewTab(),
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
            ->emptyStateHeading('No Invoices Yet')
            ->emptyStateDescription('You don\'t have any invoices yet. Invoices are created automatically when you place orders.')
            ->emptyStateIcon('heroicon-o-receipt-percent')
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
                Tabs::make('Invoice Details')
                    ->persistTab()
                    ->columnSpanFull()
                    ->tabs([
                        // Invoice Overview Tab
                        Tabs\Tab::make('Overview')
                            ->icon('heroicon-m-receipt-percent')
                            ->badge(fn (Invoice $record): string => "INV-{$record->id}")
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Invoice Information')
                                            ->description('Basic invoice details and identification')
                                            ->icon('heroicon-m-identification')
                                            ->schema([
                                                TextEntry::make('id')
                                                    ->label('Invoice ID')
                                                    ->formatStateUsing(fn ($state): string => "INV-{$state}")
                                                    ->copyable()
                                                    ->color('primary')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('iid')
                                                    ->label('External Invoice ID')
                                                    ->copyable()
                                                    ->color('gray')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->iid)),

                                                TextEntry::make('order.id')
                                                    ->label('Order ID')
                                                    ->formatStateUsing(fn ($state): string => "#{$state}")
                                                    ->copyable()
                                                    ->color('info')
                                                    ->url(fn (Invoice $record): string => route('filament.customer.resources.my-orders.orders.view', ['record' => $record->order_id])),
                                            ]),

                                        Section::make('Payment Details')
                                            ->description('Amount and payment information')
                                            ->icon('heroicon-m-currency-dollar')
                                            ->schema([
                                                TextEntry::make('price_amount')
                                                    ->label('Invoice Amount')
                                                    ->money('usd')
                                                    ->color('success')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('price_currency')
                                                    ->label('Invoice Currency')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('pay_amount')
                                                    ->label('Payable Amount')
                                                    ->money('usd')
                                                    ->color('warning')
                                                    ->visible(fn (Invoice $record): bool => $record->pay_amount !== $record->price_amount),

                                                TextEntry::make('pay_currency')
                                                    ->label('Payment Currency')
                                                    ->badge()
                                                    ->color('info')
                                                    ->visible(fn (Invoice $record): bool => $record->pay_currency !== $record->price_currency),

                                                TextEntry::make('amount_received')
                                                    ->label('Amount Received')
                                                    ->money('usd')
                                                    ->color('success')
                                                    ->weight(FontWeight::SemiBold)
                                                    ->visible(fn (Invoice $record): bool => $record->amount_received > 0),
                                            ]),

                                        Section::make('Status & Method')
                                            ->description('Payment status and method details')
                                            ->icon('heroicon-m-check-badge')
                                            ->schema([
                                                TextEntry::make('payment_status')
                                                    ->label('Payment Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'success',
                                                        'paid' => 'success',
                                                        'failed' => 'danger',
                                                        default => 'gray',
                                                    }),

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

                                                TextEntry::make('network')
                                                    ->label('Payment Network')
                                                    ->badge()
                                                    ->color('info')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->network)),
                                            ]),
                                    ]),

                                Section::make('Payment Address & Links')
                                    ->description('Payment addresses and external links')
                                    ->icon('heroicon-m-link')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('pay_address')
                                                    ->label('Payment Address')
                                                    ->copyable()
                                                    ->color('info')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->pay_address)),

                                                TextEntry::make('invoice_url')
                                                    ->label('External Payment Link')
                                                    ->url(fn (Invoice $record): string => $record->invoice_url ?? '#')
                                                    ->openUrlInNewTab()
                                                    ->copyable()
                                                    ->color('primary')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->invoice_url)),
                                            ]),
                                    ])
                                    ->visible(fn (Invoice $record): bool => !empty($record->pay_address) || !empty($record->invoice_url)),
                            ]),

                        // Order Details Tab
                        Tabs\Tab::make('Related Order')
                            ->icon('heroicon-m-shopping-bag')
                            ->schema([
                                Section::make('Order Information')
                                    ->description('Details of the related order')
                                    ->icon('heroicon-m-shopping-cart')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('order.id')
                                                    ->label('Order Number')
                                                    ->formatStateUsing(fn ($state): string => "#{$state}")
                                                    ->color('primary')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('order.grand_amount')
                                                    ->label('Order Total')
                                                    ->money('usd')
                                                    ->color('success'),

                                                TextEntry::make('order.order_status')
                                                    ->label('Order Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'new' => 'gray',
                                                        'processing' => 'warning',
                                                        'completed' => 'success',
                                                        'dispute' => 'danger',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('order.created_at')
                                                    ->label('Order Date')
                                                    ->since()
                                                    ->color('gray'),

                                                TextEntry::make('order.items_count')
                                                    ->label('Items Count')
                                                    ->badge()
                                                    ->color('info'),

                                                TextEntry::make('order.notes')
                                                    ->label('Order Notes')
                                                    ->markdown()
                                                    ->visible(fn (Invoice $record): bool => !empty($record->order->notes)),
                                            ]),
                                    ]),
                            ]),

                        // Timing Information Tab
                        Tabs\Tab::make('Timing')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Section::make('Invoice Timing')
                                    ->description('Creation, expiration and validity information')
                                    ->icon('heroicon-m-calendar')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('Invoice Created')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->since()
                                                    ->color('gray'),

                                                TextEntry::make('updated_at')
                                                    ->label('Last Updated')
                                                    ->since()
                                                    ->color('gray'),

                                                TextEntry::make('expiration_estimate_date')
                                                    ->label('Estimated Expiration')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->color('warning')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->expiration_estimate_date)),

                                                TextEntry::make('valid_until')
                                                    ->label('Valid Until')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->color('danger')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->valid_until)),

                                                TextEntry::make('time_limit')
                                                    ->label('Time Limit')
                                                    ->formatStateUsing(fn ($state): string => $state ? "{$state} seconds" : 'No limit')
                                                    ->color('info')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->time_limit)),
                                            ]),
                                    ]),
                            ]),

                        // Technical Details Tab
                        Tabs\Tab::make('Technical')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Section::make('Technical Information')
                                    ->description('Advanced technical details and configurations')
                                    ->icon('heroicon-m-wrench-screwdriver')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('payin_extra_id')
                                                    ->label('Payin Extra ID')
                                                    ->copyable()
                                                    ->visible(fn (Invoice $record): bool => !empty($record->payin_extra_id)),

                                                TextEntry::make('purchase_id')
                                                    ->label('Purchase ID')
                                                    ->copyable()
                                                    ->visible(fn (Invoice $record): bool => !empty($record->purchase_id)),

                                                TextEntry::make('smart_contract')
                                                    ->label('Smart Contract')
                                                    ->copyable()
                                                    ->visible(fn (Invoice $record): bool => !empty($record->smart_contract)),

                                                TextEntry::make('network_precision')
                                                    ->label('Network Precision')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->network_precision)),

                                                TextEntry::make('ipn_callback_url')
                                                    ->label('IPN Callback URL')
                                                    ->url(fn (Invoice $record): string => $record->ipn_callback_url ?? '#')
                                                    ->openUrlInNewTab()
                                                    ->copyable()
                                                    ->color('info')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->ipn_callback_url)),

                                                TextEntry::make('redirect_url')
                                                    ->label('Redirect URL')
                                                    ->url(fn (Invoice $record): string => $record->redirect_url ?? '#')
                                                    ->openUrlInNewTab()
                                                    ->copyable()
                                                    ->color('primary')
                                                    ->visible(fn (Invoice $record): bool => !empty($record->redirect_url)),
                                            ]),
                                    ]),
                            ]),

                        // Invoice Preview Tab
                        Tabs\Tab::make('Preview')
                            ->icon('heroicon-m-document')
                            ->schema([
                                Section::make('Invoice Preview')
                                    ->description('Preview and download your invoice')
                                    ->icon('heroicon-m-eye')
                                    ->schema([
                                        View::make('filament.infolists.components.invoice-preview')
                                            ->viewData(fn (Invoice $record) => [
                                                'invoice' => $record,
                                                'order' => $record->order,
                                                'customer' => $record->order->customer,
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
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['order.customer', 'paymentMethod'])
            ->whereHas('order', function ($query) {
                $query->where('customer_id', Auth::guard('customer')->id());
            })
            ->orderByDesc('created_at');
    }

    public static function getTabs(): array
    {
        $customerId = Auth::guard('customer')->id();
        
        return [
            'all' => Tab::make('All Invoices')
                ->icon('heroicon-m-receipt-percent')
                ->badge(
                    Invoice::whereHas('order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })->count()
                ),

            'pending' => Tab::make('Pending Payment')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'pending'))
                ->badge(
                    Invoice::whereHas('order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })->where('payment_status', 'pending')->count()
                )
                ->badgeColor('warning'),

            'paid' => Tab::make('Paid')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('payment_status', ['confirmed', 'paid']))
                ->badge(
                    Invoice::whereHas('order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })->whereIn('payment_status', ['confirmed', 'paid'])->count()
                )
                ->badgeColor('success'),

            'failed' => Tab::make('Failed')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'failed'))
                ->badge(
                    Invoice::whereHas('order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })->where('payment_status', 'failed')->count()
                )
                ->badgeColor('danger'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        $pendingCount = Invoice::whereHas('order', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })->where('payment_status', 'pending')->count();
        
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
