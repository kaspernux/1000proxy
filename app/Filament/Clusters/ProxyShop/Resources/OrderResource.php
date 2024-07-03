<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\RelationManagers\InvoiceRelationManager;
use App\Models\Order;
use App\Models\ServerPlan;
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
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use GuzzleHttp\Client;
use Filament\Forms\Components\Toggle;
use App\Models\PaymentMethod;


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
                            TextInput::make('created_at')
                                ->label('Order date'),
                            RichEditor::make('notes')
                                ->columnSpanFull()
                                ->fileAttachmentsDirectory('Order'),
                        ])->columns(2),

                    Section::make('Items List')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Select::make('server_plan_id')
                                        ->relationship('ServerPlan', 'name')
                                        ->searchable()
                                        ->columnSpan(2)
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $ServerPlan = ServerPlan::find($state);
                                            $unitAmount = (float) ($ServerPlan->price ?? 0);
                                            $quantity = (int) ($get('quantity') ?? 1);
                                            $set('unit_amount', $unitAmount);
                                            $set('total_amount', $unitAmount * $quantity);
                                            self::updatePaymentTotalAmount($get, $set);
                                        }),

                                    TextInput::make('quantity')
                                        ->required()
                                        ->numeric()
                                        ->columnSpan(2)
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
                                        ->columnSpan(2)
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
                                        ->columnSpan(2)
                                        ->required()
                                        ->numeric()
                                        ->dehydrated(false),
                                    Toggle::make('agent_bought')
                                        ->required()
                                        ->default(false)
                                ])->columns(8)
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updatePaymentTotalAmount($get, $set);
                                }),
                        ])
                ])->columnSpan(3),

                Group::make([
                    Section::make('Payment Details')
                        ->schema([
                            Placeholder::make('grand_amount_placeholder')
                                ->label('Grand Total')
                                ->columnSpanFull()
                                ->content(function (Get $get, Set $set) {
                                    $total = self::calculateTotalAmount($get, $set);
                                    $currency = $get('currency') ?? 'usd';
                                    $conversionRates = self::getConversionRates();

                                    // Check if the currency key exists in conversionRates
                                    if (!isset($conversionRates[$currency])) {
                                        // Fallback to USD if conversion rate is not available
                                        $currency = 'usd';
                                    }

                                    $conversionRate = $conversionRates[$currency] ?? 1; // Default to 1 if the rate is still not found
                                    $convertedTotal = $total * $conversionRate;

                                    $currencySymbols = [
                                        'usd' => '$',
                                        'rub' => '₽',
                                        'xmr' => 'ɱ',
                                        'btc' => '₿',
                                        'stripe' => '$',
                                    ];

                                    $decimalPlaces = in_array($currency, ['btc', 'xmr']) ? 8 : 2;
                                    return $currencySymbols[$currency] . ' ' . number_format($convertedTotal, $decimalPlaces);
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
                                    'stripe' => 'USD',
                                ])
                                ->reactive(),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                        '1' => 'Wallet',
                                        '2' => 'Stripe',
                                        '3' => 'NowPayments',
                                        '4' => 'MIR',
                                ])
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2),
                        ])->columns(2),

                    Section::make('Order Status')
                        ->schema([
                            ToggleButtons::make('order_status')
                                ->required()
                                ->columnSpanFull()
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
                                ->columnSpanFull()
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\SelectColumn::make('order_status')
                    ->options([
                            'new' => 'New',
                            'processing' => 'Processing',
                            'completed' => 'Completed',
                            'dispute' => 'Dispute',
                        ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_amount')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Payment Currency')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\SelectColumn::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                            '1' => 'Wallet',
                            '2' => 'Stripe',
                            '3' => 'NowPayments',
                            '4' => 'MIR',
                    ])
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
                //
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

    public static function updatePaymentTotalAmount(Get $get, Set $set)
    {
        $total = 0;
        if (!$repeaters = $get('items')) {
            $set('grand_amount', $total);
            return;
        }

        foreach ($repeaters as $key => $repeater) {
            $total += (float) $get("items.{$key}.total_amount");
        }

        $set('grand_amount', $total);
    }

    public static function calculateTotalAmount(Get $get, Set $set)
    {
        $items = $get('items');
        $total = 0;
        foreach ($items as $item) {
            $total += (float) ($item['total_amount'] ?? 0);
        }
        return $total;
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

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 10 ? 'success':'danger';
    }

    protected static function getConversionRates(): array
{
    $client = new Client();
    $rates = [];

    try {
        $response = $client->get('https://api.coingecko.com/api/v3/simple/price', [
            'query' => [
                'ids' => 'bitcoin,monero',
                'vs_currencies' => 'usd',
            ],
        ]);
        $data = json_decode($response->getBody()->getContents(), true);

        // Add USD conversion rate
        $rates['usd'] = 1;

        // Extract and store the conversion rates
        if (isset($data['bitcoin']['usd'])) {
            $rates['btc'] = 1 / (float) $data['bitcoin']['usd'];
        }

        if (isset($data['monero']['usd'])) {
            $rates['xmr'] = 1 / (float) $data['monero']['usd'];
        }

        // Fetch RUB to USD conversion rate
        $rubResponse = $client->get('https://v6.exchangerate-api.com/v6/b6c4172d7241466e49c86234/latest/USD');
        $rubData = json_decode($rubResponse->getBody()->getContents(), true);
        if (isset($rubData['conversion_rates']['RUB'])) {
            $rates['rub'] = (float) $rubData['conversion_rates']['RUB'];
        }
    } catch (\Exception $e) {
        // Handle exception gracefully, possibly log it
        // Ensure the USD rate is set in case of an error
        $rates['usd'] = 1;
    }

    return $rates;
}

}
