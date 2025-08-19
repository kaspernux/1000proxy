<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use BackedEnum;

class UserProfile extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected string $view = 'filament.customer.pages.user-profile';
    protected static ?int $navigationSort = 10;

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
            'timezone' => $customer->timezone ?? 'UTC',
            'locale' => $customer->locale ?? config('locales.default', 'en'),
            'theme_mode' => $customer->theme_mode ?? session('theme_mode', 'system'),
            'bio' => $customer->bio ?? '',
            'website' => $customer->website ?? '',
            'company' => $customer->company ?? '',
            'avatar' => $customer->avatar ?? null,
            // Align with model fields
            'email_notifications' => (bool) ($customer->email_notifications ?? true),
            'sms_notifications_enabled' => (bool) (is_array($customer->sms_notifications) ? ($customer->sms_notifications['enabled'] ?? false) : false),
            'marketing_opt_in' => (bool) (is_array($customer->privacy_settings) ? ($customer->privacy_settings['marketing_opt_in'] ?? false) : false),
            'login_alerts' => (bool) ($customer->login_alerts ?? true),
        ]);

        $this->twoFactorEnabled = (bool) ($customer->two_factor_enabled ?? false);
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Account Summary')
                    ->description('A snapshot of your account status and recent activity.')
                    ->schema([
                        InfoGrid::make([
                            'default' => 2,
                            'md' => 4,
                        ])->schema([
                            TextEntry::make('total_orders')
                                ->label('Total Orders')
                                ->state(fn () => (string)($this->accountStats['total_orders'] ?? 0))
                                ->icon('heroicon-o-shopping-bag')
                                ->iconColor('primary')
                                ->weight('bold'),

                            TextEntry::make('active_services')
                                ->label('Active Services')
                                ->state(fn () => (string)($this->accountStats['active_services'] ?? 0))
                                ->icon('heroicon-o-server-stack')
                                ->iconColor('success')
                                ->weight('bold'),

                            TextEntry::make('total_spent')
                                ->label('Total Spent')
                                ->state(fn () => '$' . number_format($this->accountStats['total_spent'] ?? 0, 2))
                                ->icon('heroicon-o-currency-dollar')
                                ->iconColor('warning')
                                ->weight('bold'),

                            TextEntry::make('wallet_balance')
                                ->label('Wallet Balance')
                                ->state(fn () => '$' . number_format($this->accountStats['wallet_balance'] ?? 0, 2))
                                ->icon('heroicon-o-wallet')
                                ->iconColor('info')
                                ->weight('bold'),
                        ]),
                    ])
                    ->collapsible(false),
            ]);
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
                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory(fn () => 'avatars/' . (Auth::guard('customer')->id() ?? 'guest'))
                            ->visibility('public')
                            ->helperText('Upload a square image for best results.'),
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
                                    ->unique(table: 'customers', column: 'email', ignoreRecord: true)
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

                                Select::make('locale')
                                    ->label('Language')
                                    ->options(collect(config('locales.supported', ['en']))
                                        ->mapWithKeys(fn ($l) => [$l => strtoupper($l)])
                                        ->all())
                                    ->default(fn () => config('locales.default', 'en'))
                                    ->live()
                                    ->afterStateUpdated(fn ($state) => $this->applyLocale($state))
                                    ->prefixIcon('heroicon-o-language'),

                                Select::make('theme_mode')
                                    ->label('Theme')
                                    ->options([
                                        'system' => 'System',
                                        'light' => 'Light',
                                        'dark' => 'Dark',
                                    ])
                                    ->default('system')
                                    ->live()
                                    ->afterStateUpdated(fn ($state) => $this->applyTheme($state))
                                    ->prefixIcon('heroicon-o-swatch'),

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
                                Toggle::make('email_notifications')
                                    ->label('Email Notifications')
                                    ->helperText('Receive order updates and account alerts via email')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('sms_notifications_enabled')
                                    ->label('SMS Notifications')
                                    ->helperText('Receive urgent notifications via SMS')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('marketing_opt_in')
                                    ->label('Marketing Communications')
                                    ->helperText('Receive promotional offers and updates')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('login_alerts')
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
                ->modalHeading('Change Password')
                ->modalSubmitActionLabel('Update Password')
                ->form([
                    TextInput::make('current_password')
                        ->label('Current Password')
                        ->password()
                        ->required(),
                    TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->rule(Password::defaults())
                        ->required(),
                    TextInput::make('new_password_confirmation')
                        ->label('Confirm New Password')
                        ->password()
                        ->same('new_password')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->changePassword($data);
                }),

            ActionGroup::make([
                Action::make('export_data')
                    ->label('Export My Data')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action('downloadData'),

                Action::make('toggle_2fa')
                    ->label('Toggle 2FA (Soon)')
                    ->icon('heroicon-o-shield-check')
                    ->color('gray')
                    ->action('toggleTwoFactor'),
            ])->label('Data & Security')
              ->icon('heroicon-o-cog-6-tooth'),
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
                'timezone' => $data['timezone'] ?? 'UTC',
                'locale' => $data['locale'] ?? config('locales.default', 'en'),
                'theme_mode' => $data['theme_mode'] ?? session('theme_mode', 'system'),
                'bio' => $data['bio'] ?? null,
                'website' => $data['website'] ?? null,
                'company' => $data['company'] ?? null,
                'avatar' => $data['avatar'] ?? $customer->avatar,
                'email_notifications' => (bool) ($data['email_notifications'] ?? true),
                'sms_notifications' => ['enabled' => (bool) ($data['sms_notifications_enabled'] ?? false)],
                'privacy_settings' => array_merge((array) ($customer->privacy_settings ?? []), [
                    'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
                ]),
                'login_alerts' => (bool) ($data['login_alerts'] ?? true),
                'two_factor_enabled' => (bool) ($this->twoFactorEnabled),
            ]);

            // Persist preferences to session
            session(['theme_mode' => $customer->theme_mode]);
            session(['locale' => $customer->locale]);

            // Lightweight browser hint
            $this->dispatch('theme-changed', mode: $customer->theme_mode)->toBrowser();

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

    public function applyTheme(string $mode): void
    {
        $mode = in_array($mode, ['light','dark','system'], true) ? $mode : 'system';
        $customer = Auth::guard('customer')->user();
        if ($customer && $customer->theme_mode !== $mode) {
            $customer->forceFill(['theme_mode' => $mode])->save();
        }
        session(['theme_mode' => $mode]);
        $this->dispatch('theme-changed', mode: $mode)->toBrowser();
    }

    public function applyLocale(string $locale): void
    {
        $supported = config('locales.supported', ['en']);
        $locale = in_array($locale, $supported, true) ? $locale : config('locales.default', 'en');
        $customer = Auth::guard('customer')->user();
        if ($customer && $customer->locale !== $locale) {
            $customer->forceFill(['locale' => $locale])->save();
        }
        session(['locale' => $locale]);
        app()->setLocale($locale);
        Notification::make()
            ->title('Language updated')
            ->body('Your language preference has been saved.')
            ->success()
            ->send();
    }

    public function deleteAccount(): void
    {
        Notification::make()
            ->title('Deletion request received')
            ->body('Please contact support to complete account deletion. This safeguard prevents accidental loss of data.')
            ->warning()
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
