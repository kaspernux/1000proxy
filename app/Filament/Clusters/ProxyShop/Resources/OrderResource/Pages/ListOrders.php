<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Order')
                ->icon('heroicon-o-plus'),
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
