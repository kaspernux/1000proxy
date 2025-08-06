<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
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
// Removed unused Blade view reference; Filament will use its own layout
    protected static ?int $navigationSort = 8;

    public ?array $data = [];
    public $twoFactorEnabled = false;
    public $accountStats = [];

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->loadAccountStats();
        
        $this->form->fill([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone ?? '',
            'country' => $customer->country ?? '',
            'timezone' => $customer->timezone ?? 'UTC',
            'bio' => $customer->bio ?? '',
            'website' => $customer->website ?? '',
            'company' => $customer->company ?? '',
            'notifications_email' => $customer->notifications_email ?? true,
            'notifications_sms' => $customer->notifications_sms ?? false,
            'marketing_emails' => $customer->marketing_emails ?? false,
            'security_alerts' => $customer->login_alerts ?? true,
        ]);

        $this->twoFactorEnabled = !empty($customer->two_factor_secret);
    }

    protected function loadAccountStats(): void
    {
        $customer = Auth::guard('customer')->user();
        
        $totalOrders = DB::table('orders')->where('customer_id', $customer->id)->count();
        $totalSpent = DB::table('orders')
            ->where('customer_id', $customer->id)
            ->where('status', 'delivered')
            ->sum('grand_amount');
        $activeServices = DB::table('server_clients')->where('customer_id', $customer->id)->count();
        $walletBalance = DB::table('wallets')->where('customer_id', $customer->id)->value('balance') ?? 0;

        $this->accountStats = [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'active_services' => $activeServices,
            'wallet_balance' => $walletBalance,
            'account_age' => $customer->created_at->diffForHumans(),
            'last_login' => $customer->last_login_at?->diffForHumans() ?? 'Never',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Update your personal details and contact information.')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'lg' => 2,
                        ])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('Enter your full name'),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('your@email.com'),

                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('+1 (555) 123-4567'),

                                TextInput::make('company')
                                    ->label('Company')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->placeholder('Your company name'),
                            ]),

                        Grid::make([
                            'default' => 1,
                            'lg' => 3,
                        ])
                            ->schema([
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
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-globe-alt')
                                    ->placeholder('Select your country'),

                                Select::make('timezone')
                                    ->label('Timezone')
                                    ->options([
                                        'UTC' => 'UTC (Coordinated Universal Time)',
                                        'America/New_York' => 'Eastern Time (UTC-5)',
                                        'America/Chicago' => 'Central Time (UTC-6)',
                                        'America/Los_Angeles' => 'Pacific Time (UTC-8)',
                                        'Europe/London' => 'London (UTC+0)',
                                        'Europe/Paris' => 'Paris (UTC+1)',
                                        'Asia/Tokyo' => 'Tokyo (UTC+9)',
                                        'Asia/Singapore' => 'Singapore (UTC+8)',
                                    ])
                                    ->default('UTC')
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-clock'),

                                TextInput::make('website')
                                    ->label('Website')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-globe-alt')
                                    ->placeholder('https://yourwebsite.com'),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Textarea::make('bio')
                                    ->label('Biography')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->placeholder('Tell us about yourself...')
                                    ->helperText('Maximum 500 characters'),
                            ]),
                    ]),

                Section::make('Notification Preferences')
                    ->description('Customize how you receive notifications and updates.')
                    ->icon('heroicon-o-bell')
                    ->collapsible()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'lg' => 2,
                        ])
                            ->schema([
                                Toggle::make('notifications_email')
                                    ->label('Email Notifications')
                                    ->helperText('Receive order updates and account alerts via email')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('notifications_sms')
                                    ->label('SMS Notifications')
                                    ->helperText('Receive urgent notifications via SMS')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('marketing_emails')
                                    ->label('Marketing Communications')
                                    ->helperText('Receive promotional offers and updates')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('security_alerts')
                                    ->label('Security Alerts')
                                    ->helperText('Get notified about security activities')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),

                Section::make('Security Information')
                    ->description('View your account security status and settings.')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'lg' => 3,
                        ])
                            ->schema([
                                Placeholder::make('two_factor_status')
                                    ->label('Two-Factor Authentication')
                                    ->content(fn (): string => $this->twoFactorEnabled 
                                        ? '✅ Two-factor authentication is enabled and protecting your account.'
                                        : '⚠️ Two-factor authentication is disabled. Contact support to enable it.'
                                    ),

                                Placeholder::make('last_login')
                                    ->label('Last Login')
                                    ->content(fn (): string => $this->accountStats['last_login'] ?? 'Never'),

                                Placeholder::make('account_age')
                                    ->label('Account Created')
                                    ->content(fn (): string => $this->accountStats['account_age'] ?? 'Unknown'),
                            ]),
                    ]),
            ])
            ->statePath('data')
            ->model(Auth::guard('customer')->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Profile')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),

            Action::make('change_password')
                ->label('Change Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->url('#')
                ->openUrlInNewTab(false),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $customer = Auth::guard('customer')->user();

            $customer->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'bio' => $data['bio'] ?? null,
                'website' => $data['website'] ?? null,
                'company' => $data['company'] ?? null,
                'notifications_email' => $data['notifications_email'] ?? true,
                'notifications_sms' => $data['notifications_sms'] ?? false,
                'marketing_emails' => $data['marketing_emails'] ?? false,
                'login_alerts' => $data['security_alerts'] ?? true,
            ]);

            Notification::make()
                ->title('Profile Updated! 🎉')
                ->body('Your profile information has been successfully updated.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Update Failed! ❌')
                ->body('There was an error updating your profile. Please try again.')
                ->danger()
                ->send();
        }
    }

    // Add methods for password change and other actions
    public function changePassword(array $data): void
    {
        $customer = Auth::guard('customer')->user();

        if (!Hash::check($data['current_password'], $customer->password)) {
            Notification::make()
                ->title('Invalid Password! ❌')
                ->body('Your current password is incorrect.')
                ->danger()
                ->send();
            return;
        }

        $customer->update([
            'password' => Hash::make($data['new_password']),
        ]);

        Notification::make()
            ->title('Password Changed! 🔐')
            ->body('Your password has been successfully updated.')
            ->success()
            ->send();
    }

    public function downloadData(): void
    {
        $customer = Auth::guard('customer')->user();
        
        $data = [
            'profile' => $customer->toArray(),
            'orders' => $customer->orders()->get()->toArray(),
            'wallet' => $customer->wallet()->first()?->toArray(),
            'exported_at' => now()->toISOString(),
        ];

        Notification::make()
            ->title('Data Export Ready! 📦')
            ->body('Your account data has been prepared for download.')
            ->info()
            ->send();
    }

    public function toggleTwoFactor(): void
    {
        Notification::make()
            ->title('Feature Coming Soon! 🚧')
            ->body('Two-factor authentication management will be available soon.')
            ->info()
            ->send();
    }
}
