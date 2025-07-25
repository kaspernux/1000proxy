<?php

namespace App\Filament\Customer\Clusters\MySupport\Resources\ServerRatingResource\Pages;

use App\Filament\Customer\Clusters\MySupport\Resources\ServerRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateServerRating extends CreateRecord
{
    protected static string $resource = ServerRatingResource::class;
    
     protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Attach the logged-in customer's ID
        $data['customer_id'] = Auth::guard('customer')->id();

        return $data;
    }
}
