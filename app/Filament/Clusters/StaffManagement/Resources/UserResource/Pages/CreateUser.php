<?php

namespace App\Filament\Clusters\StaffManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\StaffManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure password is hashed
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Add New Staff Member';
    }

    public function getSubheading(): string
    {
        return 'Create a new internal staff account';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Staff member created successfully';
    }
}
