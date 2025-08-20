<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getTableModel(): string
    {
        return \App\Models\Order::class;
    }

    protected function getTableQuery(): Builder
    {
        // Ensure a concrete base query is provided for Filament Tables internals on first render
        return $this->getModel()::query();
    }

    protected function getHeaderActions(): array
    {
    // Creation is disabled; orders originate from customer checkout only.
    return [
        Actions\Action::make('refresh')
            ->label('Refresh')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->action(fn () => null),
        Actions\Action::make('export_recent')
            ->label('Export Recent CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => auth()->user()?->can('export', \App\Models\Order::class))
            ->action(function () {
                $records = \App\Models\Order::query()->latest('id')->limit(1000)->get();
                $filename = 'orders-recent-' . now()->format('Ymd-His') . '.csv';
                return response()->streamDownload(function () use ($records) {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['ID', 'Customer', 'Amount', 'Currency', 'Payment', 'Order', 'Created At']);
                    foreach ($records as $order) {
                        fputcsv($out, [
                            $order->id,
                            optional($order->customer)->name,
                            $order->grand_amount,
                            $order->currency,
                            $order->payment_status,
                            $order->order_status,
                            optional($order->created_at)?->toDateTimeString(),
                        ]);
                    }
                    fclose($out);
                }, $filename, ['Content-Type' => 'text/csv']);
            }),
        Actions\Action::make('help')
            ->label('Help')
            ->icon('heroicon-o-question-mark-circle')
            ->color('gray')
            ->modalHeading('About Orders')
            ->modalContent(new \Illuminate\Support\HtmlString('Orders are created by customers at checkout. Use tabs and filters to navigate statuses, and bulk actions to export or update. Staff can assign themselves to manage an order.'))
            ->modalSubmitAction(false),
    ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders')
                ->icon('heroicon-o-list-bullet')
                ->badge(fn () => $this->getModel()::count()),

            'new' => Tab::make('New Orders')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('order_status', 'new'))
                ->badge(fn () => $this->getModel()::where('order_status', 'new')->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->icon('heroicon-o-cog-6-tooth')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('order_status', 'processing'))
                ->badge(fn () => $this->getModel()::where('order_status', 'processing')->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completed')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('order_status', 'completed'))
                ->badge(fn () => $this->getModel()::where('order_status', 'completed')->count())
                ->badgeColor('success'),

            'payment_pending' => Tab::make('Payment Pending')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('payment_status', 'pending'))
                ->badge(fn () => $this->getModel()::where('payment_status', 'pending')->count())
                ->badgeColor('warning'),

            'failed' => Tab::make('Failed/Cancelled')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('payment_status', ['failed', 'cancelled']))
                ->badge(fn () => $this->getModel()::whereIn('payment_status', ['failed', 'cancelled'])->count())
                ->badgeColor('danger'),
        ];
    }

}
