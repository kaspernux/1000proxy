<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;
use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\RelationManagers;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ActionGroup;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\View;
use Filament\Infolists\Components\Tabs;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;


class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Purchased Items';
    protected static ?string $pluralLabel = 'Purchased Items';
    protected static ?string $label = 'Purchased Item';
    protected static ?string $navigationGroup = 'My Orders';
    protected static ?int $navigationSort = 2;

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
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Item ID')->copyable()->sortable()->searchable(),
                Tables\Columns\TextColumn::make('serverPlan.name')->label('Plan')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('unit_amount')->label('Unit Price')->money('usd')->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('Quantity')->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('usd')->color('success')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Ordered At')->since()->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('View Details'),
                ]),
            ])
            ->emptyStateHeading('No Purchased Items')
            ->emptyStateDescription('Once you complete an order, your items will appear here.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Item Details')->persistTab()->tabs([
                Tabs\Tab::make('Overview')
                    ->icon('heroicon-m-receipt-percent')
                    ->schema([
                        Section::make('Purchased Item Info')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('id')->label('Item ID')->copyable()->color('primary'),
                                TextEntry::make('order.id')->label('Order ID')->copyable()->color('gray'),
                                TextEntry::make('serverPlan.name')->label('Plan Name')->color('primary'),
                                TextEntry::make('serverPlan.description')->label('Plan Description')->markdown(),
                                TextEntry::make('unit_amount')->label('Unit Price')->money('usd'),
                                TextEntry::make('quantity')->label('Quantity'),
                                TextEntry::make('total_amount')->label('Total Paid')->money('usd')->color('success'),
                            ]),
                    ]),

                // QR Codes Tab
                Tabs\Tab::make('QR Codes')
                    ->icon('heroicon-m-qr-code')
                    ->schema([
                        Section::make('📲 Proxy Configuration QR Codes')
                            ->description('Scan or download your purchased proxy configuration QR codes.')
                            ->schema([
                                RepeatableEntry::make('qr_codes')
                                    ->label(false)
                                    ->schema([
                                        ImageEntry::make('clientQr')
                                            ->label('Client QR')
                                            ->disk('public')
                                            ->openUrlInNewTab()
                                            ->getStateUsing(function ($record) {
                                                return optional(
                                                    ServerClient::query()
                                                        ->where('plan_id', $record->server_plan_id)
                                                        ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                                        ->first()
                                                )->qr_code_client;
                                            }),

                                        ImageEntry::make('subQr')
                                            ->label('Subscription QR')
                                            ->disk('public')
                                            ->openUrlInNewTab()
                                            ->getStateUsing(function ($record) {
                                                return optional(
                                                    ServerClient::query()
                                                        ->where('plan_id', $record->server_plan_id)
                                                        ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                                        ->first()
                                                )->qr_code_sub;
                                            }),

                                        ImageEntry::make('jsonQr')
                                            ->label('JSON Subscription QR')
                                            ->disk('public')
                                            ->openUrlInNewTab()
                                            ->getStateUsing(function ($record) {
                                                return optional(
                                                    ServerClient::query()
                                                        ->where('plan_id', $record->server_plan_id)
                                                        ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                                        ->first()
                                                )->qr_code_sub_json;
                                            }),
                                    ])
                                    ->default(function (OrderItem $record): array {
                                        $items = optional($record->order)->items ?? collect();

                                        return $items
                                            ->map(fn (OrderItem $item) => [
                                                'clientQr' => optional(
                                                    ServerClient::query()
                                                        ->where('plan_id', $item->server_plan_id)
                                                        ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                                        ->first()
                                                )->qr_code_client,
                                                'subQr'    => optional(
                                                    ServerClient::query()
                                                        ->where('plan_id', $item->server_plan_id)
                                                        ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                                        ->first()
                                                )->qr_code_sub,
                                                'jsonQr'   => optional(
                                                    ServerClient::query()
                                                        ->where('plan_id', $item->server_plan_id)
                                                        ->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id())
                                                        ->first()
                                                )->qr_code_sub_json,
                                            ])
                                            ->toArray();
                                    }),
                            ]),
                    ]),


                Tabs\Tab::make('Timestamps')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        Section::make('Timestamps')
                            ->schema([
                                TextEntry::make('created_at')->label('Purchased At')->since(),
                                TextEntry::make('updated_at')->label('Last Updated')->since(),
                            ]),
                    ]),
            ])
            ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderItems::route('/'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
        ];
    }
}
