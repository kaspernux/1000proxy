<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static string $view = 'filament.customer.pages.user-profile';
    protected static ?int $navigationSort = 8;

    public ?array $data = [];
    public ?array $passwordData = [];

    public $twoFactorEnabled = false;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->form->fill([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone ?? '',
            'country' => $customer->country ?? '',
            'timezone' => $customer->timezone ?? 'UTC',
            'notifications_email' => $customer->notifications_email ?? true,
            'notifications_sms' => $customer->notifications_sms ?? false,
            'marketing_emails' => $customer->marketing_emails ?? false,
        ]);

        $this->passwordForm->fill();
        $this->twoFactorEnabled = !empty($customer->two_factor_secret);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Update your account information and preferences.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),

                        Select::make('country')
                            ->label('Country')
                            ->options([
                                'US' => 'United States',
                                'CA' => 'Canada',
                                'GB' => 'United Kingdom',
                                'DE' => 'Germany',
                                'FR' => 'France',
                                'JP' => 'Japan',
                                'AU' => 'Australia',
                                'NL' => 'Netherlands',
                                'SG' => 'Singapore',
                                'HK' => 'Hong Kong',
                            ])
                            ->searchable(),

                        Select::make('timezone')
                            ->label('Timezone')
                            ->options([
                                'UTC' => 'UTC (Coordinated Universal Time)',
                                'America/New_York' => 'Eastern Time (UTC-5)',
                                'America/Chicago' => 'Central Time (UTC-6)',
                                'America/Denver' => 'Mountain Time (UTC-7)',
                                'America/Los_Angeles' => 'Pacific Time (UTC-8)',
                                'Europe/London' => 'London (UTC+0)',
                                'Europe/Paris' => 'Paris (UTC+1)',
                                'Europe/Berlin' => 'Berlin (UTC+1)',
                                'Asia/Tokyo' => 'Tokyo (UTC+9)',
                                'Asia/Shanghai' => 'Shanghai (UTC+8)',
                                'Asia/Singapore' => 'Singapore (UTC+8)',
                                'Australia/Sydney' => 'Sydney (UTC+10)',
                            ])
                            ->default('UTC')
                            ->searchable(),

                        FileUpload::make('avatar')
                            ->label('Profile Picture')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->maxSize(2048),
                    ])
                    ->columns(2),

                Section::make('Notification Preferences')
                    ->description('Choose how you want to receive notifications.')
                    ->schema([
                        Toggle::make('notifications_email')
                            ->label('Email Notifications')
                            ->helperText('Receive order updates and service notifications via email'),

                        Toggle::make('notifications_sms')
                            ->label('SMS Notifications')
                            ->helperText('Receive urgent notifications via SMS'),

                        Toggle::make('marketing_emails')
                            ->label('Marketing Emails')
                            ->helperText('Receive promotional offers and product updates'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Change Password')
                    ->description('Update your account password for better security.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->required(),

                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->confirmed(),

                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_profile')
                ->label('Save Profile')
                ->color('primary')
                ->action('saveProfile'),

            Action::make('change_password')
                ->label('Change Password')
                ->color('warning')
                ->action('changePassword'),

            Action::make('toggle_2fa')
                ->label($this->twoFactorEnabled ? 'Disable 2FA' : 'Enable 2FA')
                ->icon('heroicon-o-shield-check')
                ->color($this->twoFactorEnabled ? 'danger' : 'success')
                ->action('toggleTwoFactor'),

            Action::make('download_data')
                ->label('Download My Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action('downloadData'),
        ];
    }

    public function saveProfile(): void
    {
        try {
            $data = $this->form->getState();
            $customer = Auth::guard('customer')->user();

            DB::table('customers')
                ->where('id', $customer->id)
                ->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'country' => $data['country'],
                    'timezone' => $data['timezone'],
                    'notifications_email' => $data['notifications_email'],
                    'notifications_sms' => $data['notifications_sms'],
                    'marketing_emails' => $data['marketing_emails'],
                    'updated_at' => now(),
                ]);

            Notification::make()
                ->title('Profile Updated')
                ->body('Your profile has been successfully updated.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Update Failed')
                ->body('Unable to update your profile. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function changePassword(): void
    {
        try {
            $data = $this->passwordForm->getState();
            $customer = Auth::guard('customer')->user();

            if (!Hash::check($data['current_password'], $customer->password)) {
                Notification::make()
                    ->title('Invalid Password')
                    ->body('Your current password is incorrect.')
                    ->danger()
                    ->send();
                return;
            }

            DB::table('customers')
                ->where('id', $customer->id)
                ->update([
                    'password' => Hash::make($data['new_password']),
                    'updated_at' => now(),
                ]);

            $this->passwordForm->fill();

            Notification::make()
                ->title('Password Changed')
                ->body('Your password has been successfully updated.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Password Change Failed')
                ->body('Unable to change your password. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function toggleTwoFactor(): void
    {
        try {
            $customer = Auth::guard('customer')->user();

            if ($this->twoFactorEnabled) {
                // Disable 2FA
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'two_factor_secret' => null,
                        'two_factor_recovery_codes' => null,
                        'updated_at' => now(),
                    ]);

                $this->twoFactorEnabled = false;

                Notification::make()
                    ->title('Two-Factor Authentication Disabled')
                    ->body('Two-factor authentication has been disabled for your account.')
                    ->success()
                    ->send();
            } else {
                // Enable 2FA (simplified)
                $secret = bin2hex(random_bytes(32));

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'two_factor_secret' => $secret,
                        'updated_at' => now(),
                    ]);

                $this->twoFactorEnabled = true;

                Notification::make()
                    ->title('Two-Factor Authentication Enabled')
                    ->body('Two-factor authentication has been enabled for your account.')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('2FA Toggle Failed')
                ->body('Unable to update two-factor authentication. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function downloadData(): void
    {
        try {
            $customer = Auth::guard('customer')->user();

            $data = [
                'profile' => [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'country' => $customer->country,
                    'created_at' => $customer->created_at,
                ],
                'orders' => DB::table('orders')
                    ->where('customer_id', $customer->id)
                    ->get(),
                'server_clients' => DB::table('server_clients')
                    ->where('customer_id', $customer->id)
                    ->get(),
                'wallet' => DB::table('wallets')
                    ->where('customer_id', $customer->id)
                    ->first(),
            ];

            // Create JSON file
            $filename = 'user-data-' . $customer->id . '-' . now()->format('Y-m-d') . '.json';
            $content = json_encode($data, JSON_PRETTY_PRINT);

            // Store temporarily
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            file_put_contents(storage_path('app/temp/' . $filename), $content);

            Notification::make()
                ->title('Data Export Ready')
                ->body('Your data export has been generated successfully.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Unable to export your data. Please try again.')
                ->danger()
                ->send();
        }
    }
}
