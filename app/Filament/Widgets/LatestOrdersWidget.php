<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?string $heading = '🆕 Latest Orders';
    protected static ?string $description = 'The five most recent orders placed by customers.';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Order::query()->with('customer')->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('customer')
                    ->latest()
            )
            // Removed OrderResource reference
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency ?? 'usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn ($state) => $state ? (string) $state : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('order_status')
                    ->label('Order Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'dispute' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'new' => 'heroicon-o-sparkles',
                        'processing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-badge',
                        'dispute' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment Status')
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
                        'failed'  => 'heroicon-o-x-circle',
                        default   => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_invoice_url')
                    ->label('Invoice')
                    ->url(fn ($record) => $record->payment_invoice_url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    // Removed OrderResource reference
            ])
            ->headerActions([
                Tables\Actions\Action::make('all')
                    ->label('View All Orders')
                    ->icon('heroicon-o-queue-list')
                    // Removed OrderResource reference
                    ->color('primary')
                    ->outlined(),
            ])
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('As soon as customers place orders, they’ll appear here.')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse')
                    ->label('View All Orders')
                    ->icon('heroicon-o-queue-list')
                    // Removed OrderResource reference
            ]);
    }
}