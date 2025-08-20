<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section as SchemaSection;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\TwoFactorService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Profile & Settings';
    protected static ?int $navigationSort = 99;
    protected static ?string $slug = 'profile-settings';
    protected string $view = 'filament.admin.pages.profile-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'locale' => $user->locale ?? config('app.locale'),
            'timezone' => $user->timezone ?? config('app.timezone'),
            'theme_mode' => $user->theme_mode ?? 'system',
            'email_notifications' => (bool) ($user->email_notifications ?? true),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $timezones = collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn($tz) => [$tz => $tz])->all();

        return $schema
            ->schema([
                SchemaSection::make('Profile')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Full name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->tel()->maxLength(32),
                    ]),

                SchemaSection::make('Preferences')
                    ->icon('heroicon-o-adjustments-vertical')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('theme_mode')
                            ->options([
                                'system' => 'System',
                                'light' => 'Light',
                                'dark' => 'Dark',
                            ])->required()->native(false),
                        Forms\Components\Select::make('locale')
                            ->options(collect(config('locales.supported', ['en' => 'English']))->all())
                            ->native(false),
                        Forms\Components\Select::make('timezone')
                            ->options($timezones)
                            ->searchable()
                            ->native(false),
                    ]),

                SchemaSection::make('Notifications')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Toggle::make('email_notifications')
                            ->label('Email notifications')
                            ->inline(false),
                        SchemaSection::make('Telegram')
                            ->schema([
                                Forms\Components\Toggle::make('notify.telegram.direct')
                                    ->label('Direct messages')
                                    ->helperText('Receive account alerts via direct Telegram messages')
                                    ->default(true),
                                Forms\Components\Toggle::make('notify.telegram.security')
                                    ->label('Security alerts')
                                    ->helperText('Login alerts, 2FA, and critical changes')
                                    ->default(true),
                                Forms\Components\Toggle::make('notify.telegram.promotions')
                                    ->label('Promotions & product updates')
                                    ->default(false),
                            ])->columns(1),
                    ]),

                SchemaSection::make('Security')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($s) => null)
                            ->label('Current password'),
                        Forms\Components\TextInput::make('new_password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($s) => null)
                            ->label('New password'),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($s) => null)
                            ->label('Confirm password'),
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('Enable two-factor authentication (2FA)')
                            ->helperText('Use an authenticator app to scan the QR after enabling. Enter the 6-digit code to verify.')
                            ->live()
                            ->default(fn() => (bool) (auth()->user()->two_factor_enabled ?? false)),
                        Forms\Components\TextInput::make('two_factor_code')
                            ->label('Authenticator code')
                            ->numeric()
                            ->minLength(6)
                            ->maxLength(6)
                            // Note: In Schemas layout context, Filament passes a Schemas Get utility.
                            // Avoid strict typehint to support both Forms and Schemas Get implementations.
                            ->visible(fn($get) => (bool) $get('two_factor_enabled') && empty(auth()->user()->two_factor_secret))
                            ->dehydrateStateUsing(fn($s) => null),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = Auth::user();
        $data = $this->form->getState();
        $payload = [
            'name' => trim($data['name'] ?? $user->name),
            'email' => strtolower(trim($data['email'] ?? $user->email)),
            'phone' => $data['phone'] ?? $user->phone,
            'locale' => $data['locale'] ?? $user->locale,
            'timezone' => $data['timezone'] ?? $user->timezone,
            'theme_mode' => $data['theme_mode'] ?? $user->theme_mode,
            'email_notifications' => (bool) ($data['email_notifications'] ?? $user->email_notifications),
        ];

        // Persist granular notifications under notification_preferences JSON
        $prefs = $user->notification_preferences ?? [];
        $prefs['telegram'] = [
            'direct' => (bool) data_get($data, 'notify.telegram.direct', data_get($prefs, 'telegram.direct', true)),
            'security' => (bool) data_get($data, 'notify.telegram.security', data_get($prefs, 'telegram.security', true)),
            'promotions' => (bool) data_get($data, 'notify.telegram.promotions', data_get($prefs, 'telegram.promotions', false)),
        ];
        $payload['notification_preferences'] = $prefs;

        // Handle password change if provided
        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
                Notification::make()->title('Current password is incorrect')->danger()->send();
                return;
            }
            if (($data['new_password'] ?? '') !== ($data['new_password_confirmation'] ?? '')) {
                Notification::make()->title('Password confirmation does not match')->danger()->send();
                return;
            }
            $payload['password'] = $data['new_password'];
        }

        // 2FA toggle + verification
        $enable2fa = (bool) ($data['two_factor_enabled'] ?? $user->two_factor_enabled);
        if ($enable2fa && empty($user->two_factor_secret)) {
            $tfa = app(TwoFactorService::class);
            $pendingKey = 'tfa:pending:' . $user->id;
            $secret = Cache::get($pendingKey) ?: $tfa->generateSecret();
            $code = trim((string) ($data['two_factor_code'] ?? ''));

            if ($code === '') {
                Cache::put($pendingKey, $secret, now()->addMinutes(10));
                Notification::make()->title('Scan the QR and enter the 6-digit code to enable 2FA').info()->send();
            } elseif ($tfa->verifyCode($secret, $code)) {
                $payload['two_factor_secret'] = $secret;
                $payload['two_factor_enabled'] = true;
                $codes = collect(range(1, (int) config('security.2fa.backup_codes_count', 8)))
                    ->map(fn() => strtoupper(Str::random(10)))
                    ->all();
                $payload['two_factor_recovery_codes'] = json_encode($codes);
                Cache::forget($pendingKey);
                Notification::make()->title('Two-factor authentication enabled')->success()->send();
            } else {
                Cache::put($pendingKey, $secret, now()->addMinutes(10));
                Notification::make()->title('Invalid authenticator code, please try again')->danger()->send();
            }
        } elseif (!$enable2fa) {
            $payload['two_factor_enabled'] = false;
            $payload['two_factor_secret'] = null;
            $payload['two_factor_recovery_codes'] = null;
        }

        $user->update($payload);

        // Apply theme immediately
        session()->put('filament.dark_mode', match ($user->theme_mode) {
            'dark' => true,
            'light' => false,
            default => null,
        });

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }

    public function generateTelegramCode(): void
    {
        $user = Auth::user();
        $code = strtoupper(Str::random(6));
        Cache::put('telegram_linking_' . $code, ['type' => 'user', 'id' => $user->id], now()->addMinutes(10));

        $bot = config('services.telegram.bot_username');
        $hint = $bot ? "Send this code to @{$bot} or click t.me/{$bot}?start={$code}" : 'Send this code to the Telegram bot to link your account.';

        Notification::make()
            ->title('Telegram linking code')
            ->body("Code: {$code}\n{$hint}")
            ->success()
            ->persistent()
            ->send();
    }

    public function unlinkTelegram(): void
    {
        $user = Auth::user();
        $user->unlinkTelegram();
        Notification::make()
            ->title('Telegram unlinked')
            ->success()
            ->send();
    }

    public function getQrSvg(): ?string
    {
        $user = Auth::user();
        $secret = $user->two_factor_secret ?: Cache::get('tfa:pending:' . $user->id);
        if (!$secret) {
            return null;
        }
        $issuer = config('app.name', '1000proxy');
        $uri = app(TwoFactorService::class)->getOtpAuthUri($issuer, $user->email, $secret);
        return QrCode::format('svg')->size(180)->margin(0)->generate($uri);
    }

    public function getCurrentTwoFactorSecret(): ?string
    {
        $user = Auth::user();
        return $user->two_factor_secret ?: Cache::get('tfa:pending:' . $user->id);
    }
}
