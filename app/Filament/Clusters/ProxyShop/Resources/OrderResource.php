<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\RelationManagers\InvoiceRelationManager;
use App\Models\Order;
use App\Models\ServerClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SelectColumn;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'order_status';

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
                                        ->relationship('serverClient', 'id')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
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
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $quantity = (int) $state;
                                            $unitAmount = (float) $get('unit_amount');
                                            $set('total_amount', $quantity * $unitAmount);
                                            self::updatePaymentTotalAmount($get, $set);
                                        })
                                        ->minValue(1),
                                    TextInput::make('unit_amount')
                                        ->required()
                                        ->numeric()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $unitAmount = (float) $state;
                                            $quantity = (int) ($get('quantity') ?? 1);
                                            $set('total_amount', $unitAmount * $quantity);
                                            self::updatePaymentTotalAmount($get, $set);
                                        }),
                                    TextInput::make('total_amount')
                                        ->required()
                                        ->numeric()
                                        ->dehydrated(false),
                                    TextInput::make('agent_bought')
                                        ->required()
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updatePaymentTotalAmount($get, $set);
                                }),
                        ]),
                ])->columnSpan(3),

                Group::make([
                    Section::make('Payment Details')
                        ->schema([
                            Placeholder::make('grand_amount_placeholder')
                                ->label('Grand Total')
                                ->content(function (Get $get, Set $set) {
                                    $total = 0;
                                    if (!$repeaters = $get('items')) {
                                        return '$ ' . number_format($total, 2);
                                    }

                                    foreach ($repeaters as $key => $repeater) {
                                        $total += (float) $get("items.{$key}.total_amount");
                                    }
                                    $set('grand_amount', $total);

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
                                ->columnSpan(2),
                            TextInput::make('transaction_id')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                        ])->columns(2),

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
                        ]),

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
                        ]),
                ])->columnSpan(1),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\SelectColumn::make('order_status')
                    ->options([
                            'new' => 'New',
                            'processing' => 'Processing',
                            'completed' => 'Completed',
                            'dispute' => 'Dispute',
                        ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('payment_status')
                    ->searchable()
                    ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'failed' => 'Failed',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Add filters if needed
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoiceRelationManager::class
        ];
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

    protected static function updatePaymentTotalAmount(Get $get, Set $set)
    {
        $items = $get('items') ?? [];
        $totalAmount = array_reduce($items, function ($carry, $item) {
            return $carry + (float) ($item['total_amount'] ?? 0);
        }, 0);

        $set('grand_amount', $totalAmount);

        $currency = $get('currency') ?? 'usd';
        $currencySymbols = [
            'usd' => '$',
            'rub' => '₽',
            'xmr' => 'ɱ',
            'btc' => '₿',
            'others' => '',
        ];

        $decimalPlaces = in_array($currency, ['btc', 'xmr']) ? 8 : 2;
        $formattedTotal = $currencySymbols[$currency] . ' ' . number_format($totalAmount, $decimalPlaces);
        $set('grand_amount_placeholder', $formattedTotal);
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }
}
