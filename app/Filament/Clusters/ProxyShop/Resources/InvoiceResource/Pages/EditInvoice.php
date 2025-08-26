<?php

namespace App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource;
 use Filament\Resources\Pages\EditRecord;
 use Filament\Actions;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
