<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\RelationManagers;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Toggle;


class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = ProxyShop::class;

    public static function getLabel(): string
    {
        return 'Items';
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Order Details')
                        ->schema([
                            Select::make('order_id')
                                ->relationship('Order', 'id')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('server_plan_id')
                                ->relationship('serverPlan', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])->columns(1),
                ])->columnSpan(1),
                Group::make([
                    Section::make('Financial Details')
                        ->schema([
                            TextInput::make('unit_amount')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $quantity = (int) $get('quantity');
                                    $unitAmount = (float) $state;
                                    $set('total_amount', $quantity * $unitAmount);
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
                                }),

                            TextInput::make('total_amount')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->disabled(),
                            Toggle::make('agent_bought')
                                ->required()
                                ->default(false),
                        ])->columns(2),
                ])->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serverPlan.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('agent_bought')
                    ->boolean(),
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
            'index' => Pages\ListOrderItems::route('/'),
            'create' => Pages\CreateOrderItem::route('/create'),
            'view' => Pages\ViewOrderItem::route('/{record}'),
            'edit' => Pages\EditOrderItem::route('/{record}/edit'),
        ];
    }
}