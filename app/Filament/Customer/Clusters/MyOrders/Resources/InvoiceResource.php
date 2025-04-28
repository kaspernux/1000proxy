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


class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $pluralLabel = 'Invoices';
    protected static ?string $label = 'Invoice';
    protected static ?string $navigationGroup = 'My Orders';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->copyable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('order.grand_amount')
                    ->label('Amount')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('order.payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'paid' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('order.order_status')
                    ->label('Order Status')
                    ->color(fn ($state) => match ($state) {
                        'new' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'dispute' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Stripe' => 'gray',
                        'NowPayments' => 'info',
                        'Wallet' => 'success',
                        'Others' => 'primary',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed On')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('View Details'),

                    Action::make('Download PDF')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->color('primary')
                        ->action(function (Invoice $record) {
                            $pdf = Pdf::loadView('pdf.invoice', [
                                'invoice' => $record,
                                'order' => $record->order,
                                'customer' => $record->order->customer,
                            ]);

                            return response()->streamDownload(
                                fn () => print($pdf->stream()),
                                'Invoice-' . $record->id . '.pdf'
                            );
                        }),
                ]),
            ])
            ->emptyStateHeading('No Invoices')
            ->emptyStateDescription('You have no invoices yet.');
    }

    public static function infolist(Infolist $infolist): Infolist
{
    return $infolist->schema([
        Tabs::make('Invoice Details')->persistTab()->tabs([
            Tabs\Tab::make('Invoice Info')
                ->icon('heroicon-m-receipt-percent')
                ->schema([
                    Section::make('🧾 Invoice Overview')
                        ->description('Complete details of your invoice.')
                        ->columns([
                            'sm' => 2,
                            'md' => 3,
                            'xl' => 4,
                        ])
                        ->schema([
                            TextEntry::make('id')->label('Invoice ID')->copyable()->color('primary'),
                            TextEntry::make('iid')->label('External Invoice ID')->copyable()->color('gray'),
                            TextEntry::make('order.id')->label('Order ID')->copyable()->color('gray'),
                            TextEntry::make('price_amount')->label('Price Amount')->money('usd')->color('success'),
                            TextEntry::make('price_currency')->label('Price Currency')->badge(),
                            TextEntry::make('pay_amount')->label('Payable Amount')->money('usd'),
                            TextEntry::make('pay_currency')->label('Pay Currency')->badge(),
                            TextEntry::make('amount_received')->label('Amount Received')->money('usd')->color('success'),
                            TextEntry::make('payment_status')
                                ->label('Payment Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'pending' => 'warning',
                                    'failed' => 'danger',
                                    'paid' => 'success',
                                    default => 'gray',
                                }),
                            TextEntry::make('paymentMethod.name')->label('Payment Method')->badge()->color('primary'),
                            TextEntry::make('pay_address')->label('Pay Address')->copyable(),
                            TextEntry::make('network')->label('Network')->badge()->color('info'),
                            TextEntry::make('invoice_url')
                                ->label('Invoice URL')
                                ->url(fn ($record) => $record->invoice_url)
                                ->openUrlInNewTab()
                                ->copyable()
                                ->color('info'),
                        ]),
                ]),

            Tabs\Tab::make('Timing Info')
                ->icon('heroicon-m-clock')
                ->schema([
                    Section::make('📅 Timing and Validity')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('expiration_estimate_date')
                                ->label('Expiration Estimate')
                                ->dateTime()
                                ->color('warning'),

                            TextEntry::make('valid_until')
                                ->label('Valid Until')
                                ->dateTime()
                                ->color('warning'),

                            TextEntry::make('created_at')
                                ->label('Created At')
                                ->since()
                                ->color('gray'),

                            TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->since()
                                ->color('gray'),
                        ]),
                ]),

            Tabs\Tab::make('Advanced')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Section::make('⚙️ Advanced Invoice Properties')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('payin_extra_id')->label('Payin Extra ID'),
                            TextEntry::make('purchase_id')->label('Purchase ID'),
                            TextEntry::make('smart_contract')->label('Smart Contract'),
                            TextEntry::make('network_precision')->label('Network Precision'),
                            TextEntry::make('time_limit')->label('Time Limit (seconds)'),
                            TextEntry::make('ipn_callback_url')
                                ->label('IPN Callback URL')
                                ->url(fn ($record) => $record->ipn_callback_url)
                                ->openUrlInNewTab()
                                ->copyable()
                                ->color('info'),
                            TextEntry::make('redirect_url')
                                ->label('Redirect URL')
                                ->url(fn ($record) => $record->redirect_url)
                                ->openUrlInNewTab()
                                ->copyable()
                                ->color('info'),
                        ]),
                ]),
        ])
        ->contained(true)
        ->columnSpanFull(),
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


}
