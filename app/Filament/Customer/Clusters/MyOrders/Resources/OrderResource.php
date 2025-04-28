<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\Pages;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\ActionGroup;
use Filament\Resources\Components\Tab;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\View;
use Filament\Notifications\Notification;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $pluralLabel = 'Orders';
    protected static ?string $label = 'Order';
    protected static ?string $navigationGroup = 'My Orders'; // Grouped under My Orders
    protected static ?int $navigationSort = 1;


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
                Tables\Columns\TextColumn::make('id')->label('Order #')->copyable()->sortable()->searchable(),
                Tables\Columns\TextColumn::make('grand_amount')->label('Amount')->money('usd')->sortable(),
                Tables\Columns\BadgeColumn::make('currency')->label('Currency')->sortable(),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('order_status')
                    ->label('Order Status')
                    ->color(fn ($state) => match ($state) {
                        'new' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'dispute' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')->label('Placed On')->since()->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),

                    Action::make('Download Invoice')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->color('primary')
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

                            $pdf = Pdf::loadView('pdf.invoice', [
                                'invoice' => $invoice,
                                'order' => $record,
                                'customer' => $record->customer,
                            ]);

                            return response()->streamDownload(
                                fn () => print($pdf->stream()),
                                "Invoice-{$invoice->id}.pdf"
                            );
                        }),
                ])
            ])
            ->emptyStateHeading('No Orders')
            ->emptyStateDescription('You have no orders yet. Once you checkout, your orders will appear here.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Order Details')->tabs([
                Tabs\Tab::make('Summary')
                    ->icon('heroicon-m-receipt-percent')
                    ->schema([
                        Section::make('Order Overview')
                            ->description('Order payment and status details.')
                            ->columns([
                                'sm' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ])
                            ->schema([
                                TextEntry::make('id')->label('Order ID')->copyable(),
                                TextEntry::make('grand_amount')->label('Amount')->money('usd'),
                                TextEntry::make('currency')->label('Currency')->badge(),
                                TextEntry::make('payment_status')->label('Payment Status')->badge(),
                                TextEntry::make('order_status')->label('Order Status')->badge(),
                            ]),
                    ]),

                Tabs\Tab::make('Invoice')
                    ->icon('heroicon-m-document')
                    ->schema([
                        Section::make('Invoice')
                            ->description('Preview and download your invoice.')
                            ->schema([
                                View::make('filament.infolists.components.invoice-preview')
                                    ->visible(fn ($record) => filled($record->invoice)),
                            ]),
                    ]),

                Tabs\Tab::make('Notes')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Section::make('Notes')
                            ->description('Special notes related to this order.')
                            ->schema([
                                TextEntry::make('notes')->markdown(),
                            ]),
                    ]),

                Tabs\Tab::make('Timestamps')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        Section::make('Timestamps')
                            ->schema([
                                TextEntry::make('created_at')->label('Created At')->since(),
                                TextEntry::make('updated_at')->label('Updated At')->since(),
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['invoice', 'customer'])
            ->where('customer_id', Auth::guard('customer')->id());
    }

    public static function getTabs(): array
    {
        $customerId = Auth::guard('customer')->id();

        return [
            'all' => Tab::make('All Orders')
                ->badge(Order::where('customer_id', $customerId)->count()),

            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'paid'))
                ->badge(Order::where('customer_id', $customerId)->where('payment_status', 'paid')->count())
                ->badgeColor('success'),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'pending'))
                ->badge(Order::where('customer_id', $customerId)->where('payment_status', 'pending')->count())
                ->badgeColor('warning'),

            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'failed'))
                ->badge(Order::where('customer_id', $customerId)->where('payment_status', 'failed')->count())
                ->badgeColor('danger'),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'completed'))
                ->badge(Order::where('customer_id', $customerId)->where('order_status', 'completed')->count())
                ->badgeColor('primary'),
        ];
    }
}

