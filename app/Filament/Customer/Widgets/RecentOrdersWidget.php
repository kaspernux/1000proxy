<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables; 
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class CustomerRecentOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Orders';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public static function canView(): bool
    {
        return Auth::guard('customer')->check();
    }

    public function table(Table $table): Table
    {
        $customer = Auth::guard('customer')->user();

        return $table
            ->query(
                Order::query()
                    ->where('customer_id', $customer?->id)
                    ->latest()
            )
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->heading('Recent Orders')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency ?? 'usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'dispute' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Placed')
                    ->sortable(),
            ])
            ->emptyStateHeading('No recent orders')
            ->emptyStateDescription('New orders will show up here.')
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ]);
    }
}
