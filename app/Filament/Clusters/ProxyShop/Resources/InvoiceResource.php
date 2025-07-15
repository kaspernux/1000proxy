<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\SelectColumn;
use GuzzleHttp\Client;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $cluster = ProxyShop::class;
    protected static ?int $navigationSort = 30;

    public static function getLabel(): string
    {
        return 'Invoices';
    }

    public static function getPluralLabel(): string
    {
        return 'Invoices';
    }

    // Navigation badge for pending invoices
    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('payment_status', 'pending')
            ->orWhere('payment_status', 'waiting')
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Invoice Management')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        // Left Column
                                        Group::make([
                                            Fieldset::make('Order & Customer Details')
                                                ->schema([
                                                    Select::make('order_id')
                                                        ->label('Related Order')
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
                                                                    return [$order->id => "Order #{$order->id} - " . $order->customer->name];
                                                                })
                                                                ->toArray();
                                                        })
                                                        ->getOptionLabelUsing(function ($value): ?string {
                                                            $order = Order::find($value);
                                                            return $order ? "Order #{$order->id} - " . $order->customer->name : null;
                                                        }),

                                                    Select::make('customer_id')
                                                        ->label('Customer')
                                                        ->relationship('customer', 'name')
                                                        ->searchable(['name', 'email'])
                                                        ->preload()
                                                        ->required(),

                                                    TextInput::make('iid')
                                                        ->label('Invoice ID')
                                                        ->helperText('Internal invoice identifier')
                                                        ->placeholder('Auto-generated if empty'),

                                                    TextInput::make('order_description')
                                                        ->label('Order Description')
                                                        ->maxLength(500)
                                                        ->placeholder('Brief description of the order'),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),

                                        // Right Column
                                        Group::make([
                                            Fieldset::make('Payment Information')
                                                ->schema([
                                                    Select::make('payment_method_id')
                                                        ->label('Payment Method')
                                                        ->relationship('paymentMethod', 'name')
                                                        ->searchable()
                                                        ->preload()
                                                        ->required(),

                                                    TextInput::make('payment_id')
                                                        ->label('Payment Provider ID')
                                                        ->helperText('ID from payment provider'),

                                                    ToggleButtons::make('payment_status')
                                                        ->label('Payment Status')
                                                        ->options([
                                                            'pending' => 'Pending',
                                                            'waiting' => 'Waiting',
                                                            'confirming' => 'Confirming',
                                                            'confirmed' => 'Confirmed',
                                                            'sending' => 'Sending',
                                                            'partially_paid' => 'Partially Paid',
                                                            'finished' => 'Finished',
                                                            'failed' => 'Failed',
                                                            'refunded' => 'Refunded',
                                                            'expired' => 'Expired',
                                                        ])
                                                        ->colors([
                                                            'pending' => 'warning',
                                                            'waiting' => 'info',
                                                            'confirming' => 'warning',
                                                            'confirmed' => 'success',
                                                            'sending' => 'info',
                                                            'partially_paid' => 'warning',
                                                            'finished' => 'success',
                                                            'failed' => 'danger',
                                                            'refunded' => 'gray',
                                                            'expired' => 'danger',
                                                        ])
                                                        ->default('pending'),

                                                    TextInput::make('pay_address')
                                                        ->label('Payment Address')
                                                        ->helperText('Crypto wallet address or payment reference'),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),
                                    ]),
                            ]),

                        Tabs\Tab::make('Pricing & Currency')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        // Price Details
                                        Group::make([
                                            Fieldset::make('Price Information')
                                                ->schema([
                                                    TextInput::make('price_amount')
                                                        ->label('Price Amount')
                                                        ->numeric()
                                                        ->required()
                                                        ->step(0.01)
                                                        ->minValue(0),

                                                    Select::make('price_currency')
                                                        ->label('Price Currency')
                                                        ->options([
                                                            'USD' => 'USD',
                                                            'EUR' => 'EUR',
                                                            'RUB' => 'RUB',
                                                            'BTC' => 'BTC',
                                                            'XMR' => 'XMR',
                                                        ])
                                                        ->required()
                                                        ->default('USD'),

                                                    TextInput::make('pay_amount')
                                                        ->label('Pay Amount')
                                                        ->numeric()
                                                        ->step(0.00000001)
                                                        ->minValue(0)
                                                        ->helperText('Amount to be paid (after conversion)'),

                                                    Select::make('pay_currency')
                                                        ->label('Pay Currency')
                                                        ->options([
                                                            'USD' => 'USD',
                                                            'EUR' => 'EUR',
                                                            'RUB' => 'RUB',
                                                            'BTC' => 'BTC',
                                                            'XMR' => 'XMR',
                                                            'ETH' => 'ETH',
                                                            'LTC' => 'LTC',
                                                        ])
                                                        ->helperText('Currency customer will pay in'),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),

                                        // Exchange & Fees
                                        Group::make([
                                            Fieldset::make('Exchange & Fee Settings')
                                                ->schema([
                                                    Toggle::make('is_fixed_rate')
                                                        ->label('Fixed Exchange Rate')
                                                        ->default(true)
                                                        ->helperText('Use fixed rate for crypto conversions'),

                                                    Toggle::make('is_fee_paid_by_user')
                                                        ->label('Fee Paid by User')
                                                        ->default(true)
                                                        ->helperText('Customer pays transaction fees'),

                                                    TextInput::make('amount_received')
                                                        ->label('Amount Received')
                                                        ->numeric()
                                                        ->step(0.00000001)
                                                        ->minValue(0)
                                                        ->disabled()
                                                        ->helperText('Actual amount received'),

                                                    TextInput::make('network_precision')
                                                        ->label('Network Precision')
                                                        ->numeric()
                                                        ->default(8)
                                                        ->helperText('Decimal places for crypto'),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),
                                    ]),
                            ]),

                        Tabs\Tab::make('URLs & References')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Fieldset::make('Payment URLs')
                                    ->schema([
                                        TextInput::make('ipn_callback_url')
                                            ->label('IPN Callback URL')
                                            ->url()
                                            ->helperText('URL for payment notifications'),

                                        TextInput::make('invoice_url')
                                            ->label('Invoice URL')
                                            ->url()
                                            ->helperText('Link to view invoice'),

                                        TextInput::make('success_url')
                                            ->label('Success URL')
                                            ->url()
                                            ->helperText('Redirect after successful payment'),

                                        TextInput::make('cancel_url')
                                            ->label('Cancel URL')
                                            ->url()
                                            ->helperText('Redirect after cancelled payment'),

                                        TextInput::make('partially_paid_url')
                                            ->label('Partially Paid URL')
                                            ->url()
                                            ->helperText('Redirect for partial payments'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Advanced Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            Fieldset::make('Blockchain Details')
                                                ->schema([
                                                    TextInput::make('smart_contract')
                                                        ->label('Smart Contract Address')
                                                        ->helperText('For token payments'),

                                                    TextInput::make('network')
                                                        ->label('Blockchain Network')
                                                        ->placeholder('e.g., bitcoin, ethereum, monero'),

                                                    TextInput::make('payin_extra_id')
                                                        ->label('Payment Extra ID')
                                                        ->helperText('Memo/Tag for certain cryptocurrencies'),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),

                                        Group::make([
                                            Fieldset::make('Timing & References')
                                                ->schema([
                                                    TextInput::make('time_limit')
                                                        ->label('Time Limit (minutes)')
                                                        ->numeric()
                                                        ->default(60)
                                                        ->helperText('Payment window duration'),

                                                    DateTimePicker::make('expiration_estimate_date')
                                                        ->label('Estimated Expiration')
                                                        ->native(false),

                                                    TextInput::make('purchase_id')
                                                        ->label('Purchase ID')
                                                        ->helperText('External purchase reference'),

                                                    Select::make('wallet_transaction_id')
                                                        ->label('Wallet Transaction')
                                                        ->relationship('walletTransaction', 'id')
                                                        ->searchable()
                                                        ->preload(),
                                                ])
                                                ->columns(1),
                                        ])
                                        ->columnSpan(1),
                                    ]),
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

                TextColumn::make('iid')
                    ->label('Invoice #')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('order.id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->url(fn (Invoice $record): string =>
                        \App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages\ViewOrder::getUrl([$record->order])
                    )
                    ->color('primary'),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->tooltip(fn (Invoice $record): string => $record->customer->email ?? ''),

                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('price_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (Invoice $record): string =>
                        number_format($record->price_amount, 2) . ' ' . $record->price_currency
                    )
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('pay_amount')
                    ->label('Pay Amount')
                    ->formatStateUsing(fn (Invoice $record): string =>
                        $record->pay_amount ? number_format($record->pay_amount, 8) . ' ' . $record->pay_currency : 'N/A'
                    )
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => ['pending', 'waiting', 'confirming', 'partially_paid'],
                        'info' => ['sending'],
                        'success' => ['confirmed', 'finished'],
                        'danger' => ['failed', 'expired'],
                        'gray' => ['refunded'],
                    ])
                    ->icons([
                        'heroicon-o-clock' => ['pending', 'waiting'],
                        'heroicon-o-arrow-path' => ['confirming', 'sending'],
                        'heroicon-o-check-circle' => ['confirmed', 'finished'],
                        'heroicon-o-x-circle' => ['failed', 'expired'],
                        'heroicon-o-arrow-uturn-left' => 'refunded',
                        'heroicon-o-exclamation-triangle' => 'partially_paid',
                    ]),

                TextColumn::make('amount_received')
                    ->label('Received')
                    ->formatStateUsing(fn (Invoice $record): string =>
                        $record->amount_received ? number_format($record->amount_received, 8) : '0'
                    )
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn (Invoice $record): string => $record->created_at->format('Y-m-d H:i:s')),

                TextColumn::make('expiration_estimate_date')
                    ->label('Expires')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('No expiration')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'waiting' => 'Waiting',
                        'confirming' => 'Confirming',
                        'confirmed' => 'Confirmed',
                        'sending' => 'Sending',
                        'partially_paid' => 'Partially Paid',
                        'finished' => 'Finished',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'expired' => 'Expired',
                    ]),

                SelectFilter::make('payment_method_id')
                    ->label('Payment Method')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('price_currency')
                    ->label('Price Currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'RUB' => 'RUB',
                        'BTC' => 'BTC',
                        'XMR' => 'XMR',
                    ]),

                TernaryFilter::make('is_fixed_rate')
                    ->label('Fixed Rate')
                    ->placeholder('All invoices')
                    ->trueLabel('Fixed rate only')
                    ->falseLabel('Variable rate only'),

                Filter::make('created_at')
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
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('view_invoice')
                        ->label('View Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Invoice $record): string => $record->invoice_url ?? '#')
                        ->openUrlInNewTab()
                        ->visible(fn (Invoice $record): bool => !empty($record->invoice_url)),

                    Action::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Invoice $record): bool => !in_array($record->payment_status, ['finished', 'confirmed']))
                        ->action(function (Invoice $record) {
                            $record->update(['payment_status' => 'finished']);

                            Notification::make()
                                ->title('Invoice marked as paid')
                                ->body("Invoice #{$record->iid} has been marked as paid")
                                ->success()
                                ->send();
                        }),

                    Action::make('copy_payment_address')
                        ->label('Copy Address')
                        ->icon('heroicon-o-clipboard')
                        ->color('gray')
                        ->visible(fn (Invoice $record): bool => !empty($record->pay_address))
                        ->action(function (Invoice $record) {
                            Notification::make()
                                ->title('Payment address copied')
                                ->body("Address: {$record->pay_address}")
                                ->success()
                                ->send();
                        }),

                    Action::make('refund')
                        ->label('Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Refund Invoice')
                        ->modalDescription('This will mark the invoice as refunded. Are you sure?')
                        ->visible(fn (Invoice $record): bool => $record->payment_status === 'finished')
                        ->action(function (Invoice $record) {
                            $record->update(['payment_status' => 'refunded']);

                            Notification::make()
                                ->title('Invoice refunded')
                                ->body("Invoice #{$record->iid} has been marked as refunded")
                                ->success()
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
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if (!in_array($record->payment_status, ['finished', 'confirmed'])) {
                                    $record->update(['payment_status' => 'finished']);
                                }
                            });

                            Notification::make()
                                ->title('Invoices updated')
                                ->body($records->count() . ' invoices marked as paid')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('mark_expired')
                        ->label('Mark as Expired')
                        ->icon('heroicon-o-clock')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['payment_status' => 'expired']);

                            Notification::make()
                                ->title('Invoices expired')
                                ->body($records->count() . ' invoices marked as expired')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('When orders are created, invoices will appear here.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->striped()
            ->recordAction('view')
            ->recordUrl(
                fn (Invoice $record): string => Pages\ViewInvoice::getUrl([$record]),
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Invoice Details')->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Invoice Information')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('id')
                                        ->label('ID'),
                                    TextEntry::make('iid')
                                        ->label('Invoice #')
                                        ->copyable(),
                                    TextEntry::make('order.id')
                                        ->label('Order #')
                                        ->url(fn (Invoice $record): string =>
                                            \App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages\ViewOrder::getUrl([$record->order])
                                        ),
                                    TextEntry::make('customer.name')
                                        ->label('Customer'),
                                    TextEntry::make('paymentMethod.name')
                                        ->label('Payment Method')
                                        ->badge()
                                        ->color('info'),
                                    TextEntry::make('payment_status')
                                        ->label('Payment Status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'pending', 'waiting', 'confirming', 'partially_paid' => 'warning',
                                            'sending' => 'info',
                                            'confirmed', 'finished' => 'success',
                                            'failed', 'expired' => 'danger',
                                            'refunded' => 'gray',
                                            default => 'gray',
                                        }),
                                    TextEntry::make('created_at')
                                        ->label('Created')
                                        ->since(),
                                    TextEntry::make('updated_at')
                                        ->label('Last Updated')
                                        ->since(),
                                ]),
                        ]),

                    Tabs\Tab::make('Pricing')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Section::make('Price & Payment Details')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('price_amount')
                                        ->label('Price Amount')
                                        ->formatStateUsing(fn (Invoice $record): string =>
                                            number_format($record->price_amount, 2) . ' ' . $record->price_currency
                                        ),
                                    TextEntry::make('pay_amount')
                                        ->label('Pay Amount')
                                        ->formatStateUsing(fn (Invoice $record): string =>
                                            $record->pay_amount ? number_format($record->pay_amount, 8) . ' ' . $record->pay_currency : 'N/A'
                                        ),
                                    TextEntry::make('amount_received')
                                        ->label('Amount Received')
                                        ->formatStateUsing(fn (Invoice $record): string =>
                                            $record->amount_received ? number_format($record->amount_received, 8) : '0'
                                        ),
                                    TextEntry::make('pay_address')
                                        ->label('Payment Address')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('is_fixed_rate')
                                        ->label('Fixed Rate')
                                        ->badge()
                                        ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                                    TextEntry::make('is_fee_paid_by_user')
                                        ->label('Fee Paid by User')
                                        ->badge()
                                        ->color(fn (bool $state): string => $state ? 'info' : 'gray')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                                ]),
                        ]),

                    Tabs\Tab::make('URLs & References')
                        ->icon('heroicon-o-link')
                        ->schema([
                            Section::make('Payment URLs')
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('invoice_url')
                                        ->label('Invoice URL')
                                        ->url(fn (Invoice $record): string => $record->invoice_url ?? '#')
                                        ->openUrlInNewTab()
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('success_url')
                                        ->label('Success URL')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('cancel_url')
                                        ->label('Cancel URL')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('ipn_callback_url')
                                        ->label('IPN Callback URL')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                ]),

                            Section::make('References')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('payment_id')
                                        ->label('Payment ID')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('purchase_id')
                                        ->label('Purchase ID')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('payin_extra_id')
                                        ->label('Payment Extra ID')
                                        ->copyable()
                                        ->placeholder('Not set'),
                                    TextEntry::make('walletTransaction.id')
                                        ->label('Wallet Transaction')
                                        ->placeholder('Not linked'),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
