<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDownloadableItem extends CreateRecord
{
    protected static string $resource = DownloadableItemResource::class;

    public function mount(): void
    {
        // Redirect users away from this page since downloadable item creation is disabled
        Notification::make()
            ->title('Action Not Allowed')
            ->body('Downloadable items cannot be created manually. They are generated automatically.')
            ->warning()
            ->send();

        $this->redirect(route('filament.customer.resources.my-orders.downloadable-items.index'));
    }
}
