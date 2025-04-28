<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
}
