<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
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
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\SelectColumn;
use GuzzleHttp\Client;
use Filament\Forms\Components\Toggle;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

/*     protected static ?string $recordTitleAttribute = 'order_id';
 */
    protected static ?string $cluster = ProxyShop::class;

    public static function getLabel(): string
    {
        return 'Invoices';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Order Details')
                        ->schema([
                            Select::make('order_id')
                                ->relationship('order', 'id')
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('order_description')
                                ->columnSpan(4),
                        ])->columns(5),

                    Section::make('Payment Status URLs')
                        ->schema([
                            TextInput::make('ipn_callback_url'),
                            TextInput::make('invoice_url'),
                            TextInput::make('success_url'),
                            TextInput::make('cancel_url')->nullable(),
                            TextInput::make('partially_paid_url')->nullable(),
                        ])->columns(1),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Financial Details')
                        ->schema([
                            TextInput::make('price_amount')
                                ->required()
                                ->numeric()
                                ->prefix('$'),
                            TextInput::make('price_currency')
                                ->required()
                                ->maxLength(3),
                            Select::make('payment_method_id')
                                ->relationship('paymentMethod', 'name')
                                ->required(),
                            TextInput::make('pay_currency')
                                ->maxLength(3),
                        ])->columns(2),

                    Section::make('Rate & Commissions')
                        ->schema([
                            Toggle::make('is_fixed_rate')
                                ->required()
                                ->default(true),
                            Toggle::make('is_fee_paid_by_user')
                                ->required()
                                ->default(true),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('iid')
                    ->searchable()
                    ->label('Session ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer_id')
                    ->label('Customer ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_amount')
                    ->numeric()
                    ->sortable()
                    ->money('USD')
                    ->label('Amount'),
                Tables\Columns\TextColumn::make('payment_id')
                    ->searchable()
                    ->label('Invoice ID'),
                Tables\Columns\TextColumn::make('pay_currency')
                    ->sortable()
                    ->label('Currency'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->searchable()
                    ->label('Status'),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_currency')
                    ->label('Total Paid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pay_amount')
                    ->numeric()
                    ->sortable()
                    ->label('Payout Amount')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('order_description')
                    ->sortable()
                    ->label('Order Description')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('ipn_callback_url')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoice_url')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('success_url')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cancel_url')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('partially_paid_url')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('purchase_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_received')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payin_extra_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('smart_contract')
                    ->searchable(),
                Tables\Columns\TextColumn::make('network')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('network_precision')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('time_limit')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expiration_estimate_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_fixed_rate')
                    ->label('Fixed rate')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_fee_paid_by_user')
                    ->label('Customer Fees')
                    ->boolean()
                    ->default(true),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('redirect_url')
                    ->searchable(),
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

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 1000 ? 'success':'danger';
    }
}
