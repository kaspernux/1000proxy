<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = ProxyShop::class;

    public static function getLabel(): string
    {
        return 'Items';
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
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('server_plan_id')
                                ->relationship('serverPlan', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('server_client_id')
                                ->relationship('serverClient', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])->columns(1),
                ])->columnSpan(1),
                Group::make([
                    Section::make('Financial Details')
                        ->schema([
                            TextInput::make('agent_bought')
                                ->required()
                                ->numeric()
                                ->default(0),
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
                            TextInput::make('total_amount')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->disabled(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('serverClient.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent_bought')
                    ->numeric()
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
            // Define any additional relations if necessary
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
