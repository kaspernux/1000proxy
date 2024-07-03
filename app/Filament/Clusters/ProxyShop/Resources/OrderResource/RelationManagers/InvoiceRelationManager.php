<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Filament\Tables\Actions;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Pages\Actions\EditAction;
use Filament\Pages\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class InvoiceRelationManager extends RelationManager
{
    protected static string $relationship = 'invoice';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Order Details')
                        ->schema([
                            Select::make('order_id')
                                ->relationship('order', 'id')
                                ->required()
                                ->columns(1),
                            TextInput::make('order_description')
                                ->required()
                                ->columnSpan(5),
                        ])->columns(6),

                     Section::make('Payment Status URLs')
                        ->schema([
                            TextInput::make('ipn_callback_url')
                                ->required(),

                            TextInput::make('invoice_url')
                                ->required(),

                            TextInput::make('success_url')
                                ->required(),

                            TextInput::make('cancel_url')
                                ->nullable(),

                            TextInput::make('partially_paid_url')
                                ->nullable(),
                        ])->columns(1),


                ])->columnSpan(3),

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
                                ->required()
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
                ])->columnSpan(2),
            ])->columns(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paymentMethod.name')
                    ->sortable(),
                TextColumn::make('order.id')
                    ->sortable(),

                TextColumn::make('price_amount')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),

                TextColumn::make('pay_currency')
                    ->sortable(),

                TextColumn::make('invoice_url')
                    ->sortable(),

                TextColumn::make('is_fixed_rate')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('is_fee_paid_by_user')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
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
}