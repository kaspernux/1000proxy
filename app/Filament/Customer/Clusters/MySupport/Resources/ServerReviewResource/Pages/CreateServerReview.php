<?php

namespace App\Filament\Customer\Clusters\MySupport\Resources\ServerReviewResource\Pages;

use App\Filament\Customer\Clusters\MySupport\Resources\ServerReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateServerReview extends CreateRecord
{
    protected static string $resource = ServerReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Attach the logged-in customer's ID
        $data['customer_id'] = Auth::guard('customer')->id();

        return $data;
    }
}
