<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditOrderItem extends EditRecord
{
    protected static string $resource = OrderItemResource::class;

    public function mount(int | string $record): void
    {
        // Redirect users away from this page since order item editing is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Order items cannot be edited. They are read-only for security purposes.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.order-items.index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            // Remove delete action for security
        ];
    }
}
