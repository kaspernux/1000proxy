<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDownloadableItem extends EditRecord
{
    protected static string $resource = DownloadableItemResource::class;

    public function mount(int | string $record): void
    {
        // Redirect users away from this page since downloadable item editing is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Downloadable items cannot be edited. They are read-only for security.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.downloadable-items.index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            // Remove delete action for security
        ];
    }
}
