<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(int | string $record): void
    {
        // Redirect users away from this page since order editing is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Orders cannot be edited. They are read-only for security purposes.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.orders.index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            // Remove delete action for security
        ];
    }
}
