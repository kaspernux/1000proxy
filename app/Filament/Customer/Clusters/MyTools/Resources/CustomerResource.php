<?php

namespace App\Filament\Customer\Clusters\MyTools\Resources;

use App\Filament\Customer\Clusters\MyTools;
use App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource\Pages;
use App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Hidden;
use App\Http\Middleware\SyncCustomerPreferences;
use Illuminate\Support\Facades\Lang;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;




class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $navigationGroup = 'Account';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Account Settings')->tabs([
                Tab::make('Profile')->schema([
                    Section::make('Personal Info')
                        ->columns([
                            'sm' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])
                        ->schema([
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('email')->email()->required()->maxLength(255),
                            TextInput::make('phone')->tel(),
                            TextInput::make('telegram_chat_id')->tel()->maxLength(255),
                            TextInput::make('refcode')->nullable()->maxLength(50),
                            TextInput::make('refered_by')->nullable()->maxLength(50),
                            Select::make('timezone')
                                ->options(collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn ($tz) => [$tz => $tz]))
                                ->searchable(),
                        ]),

                    Section::make('Avatar')
                        ->columns([
                            'default' => 1,
                            'sm' => 2,
                        ])
                        ->schema([
                            FileUpload::make('image')
                                ->label('Avatar')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                ->maxSize(2048)
                                ->directory('customer_images')
                                ->visibility('public')
                                ->imageEditor()
                                ->avatar()
                                ->dehydrated(true),
                        ]),
                ]),

                Tab::make('Security')->schema([
                    Section::make('Change Password')
                        ->columns(1)
                        ->schema([
                            TextInput::make('password')
                                ->password()
                                ->label('New Password')
                                ->dehydrated(false)
                                ->afterStateHydrated(fn ($component) => $component->state(''))
                                ->helperText('Leave empty to keep your current password.'),

                            TextInput::make('password_confirmation')
                                ->password()
                                ->label('Confirm Password')
                                ->dehydrated(false)
                                ->same('password')
                                ->afterStateHydrated(fn ($component) => $component->state('')),
                        ]),
                ]),

                Tab::make('Preferences')->schema([
                    Section::make('Interface Settings')
                        ->columns([
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Select::make('theme_mode')
                                ->label(__('filament-panels::layout.actions.theme_switcher.system.label'))
                                ->options([
                                    'light' => __('filament-panels::layout.actions.theme_switcher.light.label'),
                                    'dark' => __('filament-panels::layout.actions.theme_switcher.dark.label'),
                                    'system' => __('filament-panels::layout.actions.theme_switcher.system.label'),
                                ])
                                ->default('system')
                                ->native(false)
                                ->live(false) // 🛠 Avoid conflicts with Alpine
                                ->afterStateHydrated(function ($component, $state) {
                                    // ✅ Sync theme using Alpine — this avoids Livewire reactivity issues
                                    $component->extraAttributes([
                                        'x-data' => '{}',
                                        'x-init' => <<<JS
                                            const theme = localStorage.getItem('theme') ?? '$state';
                                            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                                            const root = document.documentElement;
                        
                                            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                                                root.classList.add('dark');
                                            } else {
                                                root.classList.remove('dark');
                                            }
                                        JS,
                                        'x-on:change' => <<<JS
                                            const theme = event.target.value;
                                            localStorage.setItem('theme', theme);
                                            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                                            const root = document.documentElement;
                        
                                            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                                                root.classList.add('dark');
                                            } else {
                                                root.classList.remove('dark');
                                            }
                                        JS,
                                    ]);
                                })
                                ->dehydrated(true)
                                ->helperText(__('filament-panels::layout.actions.theme_switcher.system.label')),

                            Toggle::make('email_notifications')
                                ->label('Email Notifications')
                                ->live(false)
                                ->dehydrated(true),

                            Select::make('locale')
                                ->label('Language')
                                ->options([
                                    'en' => 'English',
                                    'fr' => 'Français',
                                    'es' => 'Español',
                                    'ru' => 'Русский',
                                ])
                                ->searchable()
                                ->live(false)
                                ->dehydrated(true),
                        ]),
                ]),
            ])->contained(true)->columnSpanFull(),
        ]);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
            session()->flash('password_updated', true);
        }

        if (request()->hasFile('image')) {
            logger('Uploaded image mime: ' . request()->file('image')->getMimeType());
        }
        

        logger($data);
        return $data;
        
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewCustomer::route('/'),
            'edit' => Pages\EditCustomer::route('/edit'),
        ];
    }


}