<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateOrderItem extends CreateRecord
{
    protected static string $resource = OrderItemResource::class;

    public function mount(): void
    {
        // Redirect users away from this page since order item creation is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Order items cannot be created manually. They are created automatically when you place orders.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.order-items.index'));
    }
}
