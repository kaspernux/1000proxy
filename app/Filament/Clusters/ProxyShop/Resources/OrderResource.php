<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\ServerClient;
use Filament\Forms;
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
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteBulkAction;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $cluster = ProxyShop::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Order Information')
                        ->schema([
                            Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            DatePicker::make('order_date')
                                ->required(),
                            MarkdownEditor::make('notes')
                                ->columnSpanFull()
                                ->fileAttachmentsDirectory('Order'),
                        ])->columns(2),

                    Section::make('Items List')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Select::make('server_plan_id')
                                        ->relationship('serverPlan', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    Select::make('server_client_id')
                                        ->relationship('serverClient', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $serverClient = ServerClient::find($state);
                                            $unitAmount = (float) ($serverClient->price ?? 0);
                                            $quantity = (int) ($get('quantity') ?? 1);
                                            $set('unit_amount', $unitAmount);
                                            $set('total_amount', $unitAmount * $quantity);
                                            self::updatePaymentTotalAmount($get, $set);
                                        }),

                                    TextInput::make('quantity')
                                        ->required()
                                        ->numeric()
                                        ->default(1)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $quantity = (int) $state;
                                            $unitAmount = (float) $get('unit_amount');
                                            $set('total_amount', $quantity * $unitAmount);
                                            self::updatePaymentTotalAmount($get, $set);
                                        })
                                        ->minValue(1),

                                    TextInput::make('unit_amount')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->disabled(),

                                    TextInput::make('total_amount')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->disabled(),
                                ])->columns(2),
                        ]),

                    Section::make('Payment Details')
                        ->schema([
                            Placeholder::make('grand_total_placeholder')
                                ->label('Grand Total')
                                ->content(function (Forms\Get $get, Forms\Set $set) {
                                    $total = 0;
                                    if (!$repeaters = $get('items')) {
                                        return '$ ' . number_format($total, 2);
                                    }

                                    foreach ($repeaters as $key => $repeater) {
                                        $total += (float) $get("items.{$key}.total_amount");
                                    }
                                    $set('grand_total', $total);

                                    $currency = $get('currency') ?? 'usd';
                                    $currencySymbols = [
                                        'usd' => '$',
                                        'rub' => '₽',
                                        'xmr' => 'ɱ',
                                        'btc' => '₿',
                                        'others' => '',
                                    ];

                                    $decimalPlaces = in_array($currency, ['btc', 'xmr']) ? 8 : 2;
                                    return $currencySymbols[$currency] . ' ' . number_format($total, $decimalPlaces);
                                }),

                            Hidden::make('grand_amount')
                                ->default(0),

                            Select::make('currency')
                                ->required()
                                ->default('usd')
                                ->columnSpan(2)
                                ->options([
                                    'usd' => 'USD',
                                    'rub' => 'RUB',
                                    'xmr' => 'XMR',
                                    'btc' => 'BTC',
                                    'others' => 'Others',
                                ])
                                ->reactive(),

                            Select::make('payment_method_id')
                                ->label('Payment Method')
                                ->relationship('paymentMethod', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columns(2),
                            TextInput::make('transaction_id')
                                ->disabled()
                                ->default(function () {
                                    $specialTag = 'ORD'; // Define your special tag here
                                    $uuid = (string) Str::uuid();
                                    return $specialTag . '-' . $uuid;
                                }),
                        ])->columns(2),
                ])->columnSpan(2),

                Group::make([
                        Section::make('Order Status')
                            ->schema([
                                ToggleButtons::make('order_status')
                                    ->required()
                                    ->default('new')
                                    ->options([
                                        'new' => 'New',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'dispute' => 'Dispute',
                                    ])
                                    ->colors([
                                        'new' => 'info',
                                        'processing' => 'warning',
                                        'completed' => 'success',
                                        'dispute' => 'danger',
                                    ])
                                    ->icons([
                                        'new' => 'heroicon-o-sparkles',
                                        'processing' => 'heroicon-o-arrow-path',
                                        'completed' => 'heroicon-o-check-badge',
                                        'dispute' => 'heroicon-o-eye',
                                    ]),
                            ])->columns(1),

                        Section::make('Payment Status')
                            ->schema([
                                ToggleButtons::make('payment_status')
                                    ->required()
                                    ->default('pending')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'failed' => 'Failed',
                                    ])
                                    ->colors([
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'failed' => 'danger',
                                    ])
                                    ->icons([
                                        'pending' => 'heroicon-o-exclamation-circle',
                                        'paid' => 'heroicon-o-check-circle',
                                        'failed' => 'heroicon-o-exclamation-triangle',
                                    ]),
                            ])->columns(1),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    protected static function updatePaymentTotalAmount($get, $set)
    {
        $items = $get('items');
        $grandAmount = array_sum(array_column($items, 'total_amount'));
        $set('grand_amount', $grandAmount);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('grand_amount')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('currency')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('paymentMethod.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('order_status')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // Add your filters here if any
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}