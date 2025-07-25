<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;
    
}
