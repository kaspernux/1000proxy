<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Edit User: ' . $this->record->name;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only hash password if it's been changed
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Remove password from data if it's empty (keep existing password)
            unset($data['password']);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'User updated successfully';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View User')
                ->icon('heroicon-o-eye'),
            
            Actions\DeleteAction::make()
                ->label('Delete User')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete User')
                ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete user'),
            
            Actions\Action::make('reset_password')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->helperText('User will need to use this new password to login'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'password' => Hash::make($data['new_password'])
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Password Reset')
                        ->body('Password has been reset successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Reset Password')
                ->modalDescription('This will change the user\'s password. They will need to use the new password to login.'),
        ];
    }
}
