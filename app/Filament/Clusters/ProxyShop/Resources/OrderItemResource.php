<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\RelationManagers;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderItemResource extends Resource
    {
    protected static ?string $model = OrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = ProxyShop::class;

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Group::make([
                    Section::make('Order Details')
                        ->schema([
                            Forms\Components\Select::make('order_id')
                                ->relationship('order', 'id')
                                ->required(),
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->required(),
                            Forms\Components\Select::make('server_id')
                                ->relationship('server', 'name')
                                ->required(),
                            Forms\Components\Select::make('server_plan_id')
                                ->relationship('serverPlan', 'title'),
                        ])->columns(2),
                    Section::make('Financial Details')
                        ->schema([
                            Forms\Components\TextInput::make('status')
                                ->required()
                                ->numeric()
                                ->columnSpan(2),
                            Forms\Components\DatePicker::make('date')
                                ->required(),
                            Forms\Components\Select::make('payments_id')
                                ->relationship('payments', 'id'),
                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('notif')
                                ->required()
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('rahgozar')
                                ->required()
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('agent_bought')
                                ->required()
                                ->numeric()
                                ->default(0),
                        ])->columns(2),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Additional Information')
                        ->schema([
                            Forms\Components\TextInput::make('server_inbound_id')
                                ->numeric(),
                            Forms\Components\TextInput::make('token')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('fileid')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('remark')
                                ->maxLength(255),
                        ])->columns(2),
                    Section::make('Technical Details')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('protocol')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\DatePicker::make('expire_date')
                                ->required(),
                            Forms\Components\Textarea::make('link')
                                ->required()
                                ->columnSpan(2),
                        ])->columns(2),
                ])->columnSpan(2),
            ])->columns(4);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('server.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serverPlan.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('server_inbound_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('token')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payments.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fileid')
                    ->numeric()

                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('protocol')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expire_date')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notif')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rahgozar')
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