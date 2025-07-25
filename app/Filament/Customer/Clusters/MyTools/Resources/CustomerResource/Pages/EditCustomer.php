<?php

namespace App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource\Pages;

use App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function mount(string|int|null $record = null): void
    {
        parent::mount(auth('customer')->id());
    }

    protected function afterSave(): void
    {
        $customer = $this->record;

        session()->put('locale', $customer->locale);
        app()->setLocale($customer->locale);

        // Sync theme mode for Filament (used by defaultThemeMode)
        session()->put('theme_mode', $customer->theme_mode);

        // Filament uses this boolean session key for dark mode
        session()->put('filament.dark_mode', match ($customer->theme_mode) {
            'dark' => true,
            'light' => false,
            default => null, // 'system' mode
        });

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();

        // Optional: trigger a page reload for the new theme to apply
        // $this->redirect(request()->fullUrl()); 
    }

}
