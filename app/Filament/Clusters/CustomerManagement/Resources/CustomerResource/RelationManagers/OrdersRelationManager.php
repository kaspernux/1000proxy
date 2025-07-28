<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SelectColumn;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $title = 'Orders';
    protected static ?string $modelLabel = 'Order';
    protected static ?string $pluralModelLabel = 'Orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_id')
                            ->label('Order ID')
                            ->required()
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount ($)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required(),
                        
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'stripe' => 'Stripe (Card)',
                                'paypal' => 'PayPal',
                                'crypto' => 'Cryptocurrency',
                                'wallet' => 'Wallet Balance',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_id')
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state){
                        'new' => 'info',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state){
                        'new' => 'heroicon-o-sparkles',
                        'processing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-badge',
                        'failed' => 'heroicon-o-eye',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'crypto' => 'Crypto',
                        'wallet' => 'Wallet',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'primary' => 'stripe',
                        'info' => 'paypal',
                        'warning' => 'crypto',
                        'success' => 'wallet',
                    ]),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state){
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state){
                        'pending' => 'heroicon-o-exclamation-circle',
                        'paid' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-exclamation-triangle',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('orderItems.server.name')
                    ->label('Services')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->orderItems->pluck('server.name')->join(', ');
                    }),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'crypto' => 'Cryptocurrency',
                        'wallet' => 'Wallet',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),

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
            ])
            ->defaultSort('created_at', 'desc');
    }
}
