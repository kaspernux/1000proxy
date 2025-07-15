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
                    ->with(['customer', 'orderItems.serverPlan'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => 'Order ' . ucfirst($record->status))
                    ->colors([
                        'success' => fn ($state) => str_contains($state, 'completed'),
                        'warning' => fn ($state) => str_contains($state, 'pending'),
                        'danger' => fn ($state) => str_contains($state, 'failed'),
                        'info' => fn ($state) => str_contains($state, 'processing'),
                    ]),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Activity Description')
                    ->getStateUsing(fn ($record) => "Order #{$record->id} - " . $record->orderItems->pluck('serverPlan.name')->join(', '))
                    ->limit(60),
                Tables\Columns\TextColumn::make('total')
                    ->label('Value')
                    ->money('USD')
                    ->sortable(),
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
