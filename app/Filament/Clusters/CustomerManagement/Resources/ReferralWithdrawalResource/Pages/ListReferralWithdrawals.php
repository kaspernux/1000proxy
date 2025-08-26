<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\ReferralWithdrawalResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\ReferralWithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralWithdrawals extends ListRecords
{
    protected static string $resource = ReferralWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
