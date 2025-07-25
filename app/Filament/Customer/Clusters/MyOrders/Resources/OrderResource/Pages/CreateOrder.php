<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(): void
    {
        // Redirect users away from this page since order creation is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Orders cannot be created manually. Please use the shop to place orders.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.orders.index'));
    }
}
