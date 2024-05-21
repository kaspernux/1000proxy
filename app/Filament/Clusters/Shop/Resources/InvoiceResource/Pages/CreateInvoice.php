<?php

namespace App\Filament\Clusters\Shop\Resources\InvoiceResource\Pages;

use App\Filament\Clusters\Shop\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
}