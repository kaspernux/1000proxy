<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(int | string $record): void
    {
        // Redirect users away from this page since invoice editing is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Invoices cannot be edited. They are read-only for security and compliance.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.invoices.index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            // Remove delete action for security
        ];
    }
}
