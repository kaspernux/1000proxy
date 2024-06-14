<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\OrderResource\Widgets\OrderStats; // Ensure this use statement is correct
use App\Filament\Clusters\ProxyShop\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;


class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array{
        return [
            OrderStats::class
        ];
    }
    public function getTabs(): array{
        return[
          null => Tab::make('All'),
          'new' => Tab::make()->query(fn ($query) => $query -> where('order_status', 'new')),
          'completed' => Tab::make()->query(fn ($query) => $query -> where('order_status', 'completed')),
          'processing' => Tab::make()->query(fn ($query) => $query -> where('order_status', 'processing')),
          'dispute' => Tab::make()->query(fn ($query) => $query -> where('order_status', 'dispute')),

        ];
    }

}