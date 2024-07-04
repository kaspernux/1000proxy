<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\PaymentResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ProxyShop::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\TextInput::make('payment_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_status')
                    ->required()
                    ->maxLength(255)
                    ->default('waiting'),
                Forms\Components\TextInput::make('pay_address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('price_currency')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('pay_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('pay_currency')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Forms\Components\TextInput::make('order_description')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ipn_callback_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('purchase_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount_received')
                    ->numeric(),
                Forms\Components\TextInput::make('payin_extra_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('smart_contract')
                    ->maxLength(255),
                Forms\Components\TextInput::make('network')
                    ->maxLength(255),
                Forms\Components\TextInput::make('network_precision')
                    ->maxLength(255),
                Forms\Components\TextInput::make('time_limit')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('expiration_estimate_date'),
                Forms\Components\Toggle::make('is_fixed_rate')
                    ->required(),
                Forms\Components\Toggle::make('is_fee_paid_by_user')
                    ->required(),
                Forms\Components\DateTimePicker::make('valid_until'),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('redirect_url')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pay_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pay_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ipn_callback_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_received')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payin_extra_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('smart_contract')
                    ->searchable(),
                Tables\Columns\TextColumn::make('network')
                    ->searchable(),
                Tables\Columns\TextColumn::make('network_precision')
                    ->searchable(),
                Tables\Columns\TextColumn::make('time_limit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expiration_estimate_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_fixed_rate')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_fee_paid_by_user')
                    ->boolean(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
