<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(): void
    {
        // Redirect users away from this page since invoice creation is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Invoices cannot be created manually. They are generated automatically for orders.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.invoices.index'));
    }
}
