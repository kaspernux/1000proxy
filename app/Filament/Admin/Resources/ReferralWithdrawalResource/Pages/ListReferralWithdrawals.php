<?php

namespace App\Filament\Admin\Resources\ReferralWithdrawalResource\Pages;

use App\Filament\Admin\Resources\ReferralWithdrawalResource;
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
