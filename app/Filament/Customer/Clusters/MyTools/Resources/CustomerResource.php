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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $navigationGroup = 'Account';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Account Settings')->tabs([
                    Tab::make('Profile')
                        ->icon('heroicon-o-clipboard-document')
                        ->schema([
                            Section::make('Personal Info')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->placeholder('Jane Doe'),
                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required(),
                                        TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->hint('+1 (555) 123-4567'),
                                        Select::make('timezone')
                                            ->label('Time Zone')
                                            ->options(\DateTimeZone::listIdentifiers())
                                            ->searchable(),
                                    ]),
                                ]),
                            Section::make('Avatar')
                                ->schema([
                                    FileUpload::make('image')
                                        ->label('Profile Photo')
                                        ->image()
                                        ->avatar()                    // circular preview
                                        ->directory('customers/avatars')
                                        ->maxSize(1024)
                                        ->helperText('PNG or JPG, ≤1 MB'),
                                ]),
                        ]),

                    Tab::make('Security')
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            Section::make('Change Password')
                                ->description('Leave blank to keep your current password.')
                                ->schema([
                                    TextInput::make('password')
                                        ->label('New Password')
                                        ->password()
                                        ->dehydrated(fn ($state) => filled($state))
                                        ->helperText('Type only if you’d like to change it.'),
                                ]),
                        ]),

                    Tab::make('Preferences')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Card::make()->schema([
                                Toggle::make('email_notifications')
                                    ->label('Email Notifications')
                                    ->helperText('Receive order updates via email.'),
                                Toggle::make('dark_mode')
                                    ->label('Dark Mode')
                                    ->helperText('Switch your dashboard theme.'),
                            ]),
                            Section::make('Language & Locale')
                                ->schema([
                                    Select::make('locale')
                                        ->label('Language')
                                        ->options([
                                            'en' => 'English',
                                            'ru' => 'Русский',
                                            'es' => 'Español',
                                        ])
                                        ->searchable(),
                                ]),
                        ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            // “index” is now your ViewRecord page at `/account/customers`
            'index' => Pages\ViewCustomer::route('/'),
            // “edit” is at `/account/customers/edit`
            'edit'  => Pages\EditCustomer::route('/edit'),
        ];
    }

}

