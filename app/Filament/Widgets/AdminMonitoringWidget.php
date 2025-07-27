<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AdminMonitoringWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent System Activities';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['customer'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\BadgeColumn::make('order_status')
                    ->label('Order Status')
                    ->colors([
                        'success' => fn ($state) => $state === 'completed',
                        'warning' => fn ($state) => $state === 'processing' || $state === 'new',
                        'danger' => fn ($state) => $state === 'dispute',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->colors([
                        'success' => fn ($state) => $state === 'paid',
                        'warning' => fn ($state) => $state === 'pending',
                        'danger' => fn ($state) => $state === 'failed',
                    ]),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_amount')
                    ->label('Value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(60),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15])
            ->poll('60s');
    }
}

class AdminRecentActivitiesWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Customer Activities';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->with(['orders.orderItems.serverPlan'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->getStateUsing(fn ($record) => $record->orders->sum('total'))
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_order')
                    ->label('Last Order')
                    ->getStateUsing(fn ($record) => $record->orders->first()?->created_at?->diffForHumans() ?? 'Never')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15])
            ->poll('120s')
            ->actions([
                Tables\Actions\Action::make('view_orders')
                    ->label('Orders')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn (Customer $record) => route('filament.admin.clusters.proxy-shop.resources.orders.index', ['tableFilters[customer_id][value]' => $record->id])),
            ]);
    }
}