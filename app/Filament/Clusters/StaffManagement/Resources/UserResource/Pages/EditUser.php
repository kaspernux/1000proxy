<?php

namespace App\Filament\Clusters\StaffManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\StaffManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only hash password if it's being changed
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Edit Staff Member';
    }

    public function getSubheading(): string
    {
        return 'Update staff member information and permissions';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Staff member updated successfully';
    }
}
