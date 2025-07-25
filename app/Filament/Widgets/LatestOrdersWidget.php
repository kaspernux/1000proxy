<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\ProxyShop\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?string $heading = '🆕 Latest Orders';
    protected static ?string $description = 'The five most recent orders placed by customers.';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            // Use the same base query as your OrderResource
            ->query(OrderResource::getEloquentQuery())
            // Show only 5 per page in this widget
            ->defaultPaginationPageOption(5)
            // Newest first
            ->defaultSort('created_at', 'desc')
            // Polished styling
            ->striped()
            // Columns
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_amount')
                    ->label('Amount (USD)')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'new' => 'heroicon-o-sparkles',
                        'processing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-badge',
                        'failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid'    => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'paid'    => 'heroicon-o-banknotes',
                        'failed'  => 'heroicon-o-exclamation-triangle',
                        default   => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Single “View Order” button per row
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            // Header action to go to full list
            ->headerActions([
                Tables\Actions\Action::make('all')
                    ->label('View All Orders')
                    ->icon('heroicon-o-queue-list')
                    ->url(OrderResource::getUrl('index'))
                    ->color('primary')
                    ->outlined(),
            ])
            // Empty state
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('As soon as customers place orders, they’ll appear here.')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse')
                    ->label('View All Orders')
                    ->icon('heroicon-o-queue-list')
                    ->url(OrderResource::getUrl('index')),
            ]);
    }
}
