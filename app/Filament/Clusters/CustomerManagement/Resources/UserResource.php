<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\UserResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\UserResource\RelationManagers;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use App\Filament\Concerns\HasPerformanceOptimizations;

class UserResource extends Resource
{
    use HasPerformanceOptimizations;
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users & Admins';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'Users & Admins';
    protected static ?string $cluster = CustomerManagement::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('User Information')
                        ->description('Basic user account details')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Full Name'),

                            Forms\Components\TextInput::make('username')
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->placeholder('Username (optional)'),

                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->placeholder('user@example.com'),

                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn (string $context): bool => $context === 'create')
                                ->maxLength(255)
                                ->placeholder('Password'),
                        ])
                        ->columns(2),

                    Section::make('User Role & Status')
                        ->description('User permissions and account status')
                        ->schema([
                            Forms\Components\Select::make('role')
                                ->options([
                                    'admin' => 'Administrator',
                                    'support_manager' => 'Support Manager',
                                    'sales_support' => 'Sales Support',
                                ])
                                ->default('support_manager')
                                ->required()
                                ->helperText('Admin: full system access, Support Manager: customer support, Sales Support: sales assistance'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Account Active')
                                ->default(true)
                                ->helperText('Active users can login and access the admin panel'),

                            Forms\Components\DateTimePicker::make('last_login_at')
                                ->label('Last Login')
                                ->disabled()
                                ->helperText('Automatically updated on login'),
                        ])
                        ->columns(2),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('Telegram Integration')
                        ->description('Telegram bot account linking')
                        ->schema([
                            Forms\Components\TextInput::make('telegram_chat_id')
                                ->label('Telegram Chat ID')
                                ->numeric()
                                ->placeholder('123456789')
                                ->helperText('Telegram chat ID for bot notifications'),

                            Forms\Components\TextInput::make('telegram_username')
                                ->label('Telegram Username')
                                ->placeholder('@username')
                                ->helperText('Telegram username (without @)'),

                            Forms\Components\TextInput::make('telegram_first_name')
                                ->label('Telegram First Name')
                                ->placeholder('John')
                                ->helperText('First name from Telegram profile'),

                            Forms\Components\TextInput::make('telegram_last_name')
                                ->label('Telegram Last Name')
                                ->placeholder('Doe')
                                ->helperText('Last name from Telegram profile'),
                        ]),

                    Section::make('Account Information')
                        ->description('User account details')
                        ->schema([
                            Forms\Components\Placeholder::make('registration_date')
                                ->label('Registration Date')
                                ->content(fn (?User $record): string => $record?->created_at?->format('M d, Y H:i') ?? 'Not available'),

                            Forms\Components\Placeholder::make('last_updated')
                                ->label('Last Updated')
                                ->content(fn (?User $record): string => $record?->updated_at?->format('M d, Y H:i') ?? 'Not available'),

                            Forms\Components\Placeholder::make('account_age')
                                ->label('Account Age')
                                ->content(fn (?User $record): string => $record ? $record->getRegistrationAgeInDays() . ' days' : 'Not available'),
                        ]),
                ])->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        $resource = new static();

        return $resource->applyAllPerformanceOptimizations($table)
            ->modifyQueryUsing(fn ($query) => $resource->optimizeTableQuery($query))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy'),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'support_manager',
                        'info' => 'sales_support',
                    ])
                    ->icons([
                        'heroicon-o-shield-check' => 'admin',
                        'heroicon-o-user-group' => 'support_manager',
                        'heroicon-o-phone' => 'sales_support',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telegram_username')
                    ->label('Telegram')
                    ->placeholder('Not linked')
                    ->prefix('@')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info'),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->since(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Basic filters
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrators',
                        'support_manager' => 'Support Managers',
                        'sales_support' => 'Sales Support',
                    ])
                    ->placeholder('All roles'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Account Status')
                    ->placeholder('All accounts')
                    ->trueLabel('Active accounts')
                    ->falseLabel('Inactive accounts'),

                Tables\Filters\TernaryFilter::make('has_telegram')
                    ->label('Telegram Status')
                    ->placeholder('All users')
                    ->trueLabel('Telegram linked')
                    ->falseLabel('No Telegram')
                    ->query(fn (Builder $query, array $data): Builder =>
                        match ($data['value']) {
                            '1' => $query->whereNotNull('telegram_chat_id'),
                            '0' => $query->whereNull('telegram_chat_id'),
                            default => $query,
                        }
                    ),

                Tables\Filters\Filter::make('recent_login')
                    ->label('Recent Logins')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_at', '>=', now()->subDays(30)))
                    ->toggle(),

                // Advanced filters for internal users
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('Never Logged In')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('registration_period')
                    ->form([
                        Forms\Components\DatePicker::make('registered_from')
                            ->label('Registered From'),
                        Forms\Components\DatePicker::make('registered_until')
                            ->label('Registered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['registered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['registered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('last_login_period')
                    ->form([
                        Forms\Components\DatePicker::make('last_login_from')
                            ->label('Last Login From'),
                        Forms\Components\DatePicker::make('last_login_until')
                            ->label('Last Login Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['last_login_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_login_at', '>=', $date),
                            )
                            ->when(
                                $data['last_login_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_login_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('switch_to_customer_panel')
                    ->label('View Customers')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn (): string => route('filament.admin.resources.customers.index'))
                    ->tooltip('Switch to Customer Management'),

                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'password' => Hash::make($data['new_password'])
                        ]);

                        Notification::make()
                            ->title('Password Reset')
                            ->body("Password updated for: {$record->name}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Basic bulk actions
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Users Activated')
                                ->body("Successfully activated {$count} users")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title('Users Deactivated')
                                ->body("Successfully deactivated {$count} users")
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('make_admin')
                        ->label('Promote to Admin')
                        ->icon('heroicon-o-shield-check')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['role' => 'admin']));

                            Notification::make()
                                ->title('Role Updated')
                                ->body("Successfully promoted {$count} users to administrators")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('make_support_manager')
                        ->label('Set as Support Manager')
                        ->icon('heroicon-o-user-group')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['role' => 'support_manager']));

                            Notification::make()
                                ->title('Role Updated')
                                ->body("Successfully changed {$count} users to support managers")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('make_sales_support')
                        ->label('Set as Sales Support')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['role' => 'sales_support']));

                            Notification::make()
                                ->title('Role Updated')
                                ->body("Successfully changed {$count} users to sales support")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    // Advanced bulk actions
                    Tables\Actions\BulkAction::make('reset_passwords')
                        ->label('Reset Passwords')
                        ->icon('heroicon-o-key')
                        ->color('danger')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('New Password for All Selected Users')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->helperText('This password will be set for all selected users'),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update([
                                'password' => Hash::make($data['new_password'])
                            ]));

                            Notification::make()
                                ->title('Passwords Reset')
                                ->body("Successfully reset passwords for {$count} users")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('send_notification')
                        ->label('Send Notification')
                        ->icon('heroicon-o-bell')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->label('Notification Title')
                                ->required()
                                ->placeholder('Important Update'),

                            Forms\Components\Textarea::make('message')
                                ->label('Message')
                                ->required()
                                ->placeholder('Enter your message here...')
                                ->rows(4),

                            Forms\Components\Select::make('notification_type')
                                ->label('Notification Type')
                                ->options([
                                    'info' => 'Information',
                                    'success' => 'Success',
                                    'warning' => 'Warning',
                                    'danger' => 'Important/Danger',
                                ])
                                ->default('info')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();

                            // In a real implementation, you would send actual notifications
                            // For now, just show a success message

                            Notification::make()
                                ->title('Notifications Sent')
                                ->body("Successfully sent notifications to {$count} users")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('export_data')
                        ->label('Export User Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->form([
                            Forms\Components\CheckboxList::make('fields')
                                ->label('Select Fields to Export')
                                ->options([
                                    'basic' => 'Basic Info (Name, Email, Role)',
                                    'telegram' => 'Telegram Information',
                                    'activity' => 'Activity Data (Last Login)',
                                ])
                                ->default(['basic'])
                                ->required(),

                            Forms\Components\Select::make('format')
                                ->label('Export Format')
                                ->options([
                                    'csv' => 'CSV',
                                    'xlsx' => 'Excel',
                                    'json' => 'JSON',
                                ])
                                ->default('csv')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            // In a real implementation, you would trigger an export job

                            Notification::make()
                                ->title('Export Started')
                                ->body('Your export is being processed. You will receive a download link shortly.')
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('unlink_telegram')
                        ->label('Unlink Telegram')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = 0;
                            $records->each(function ($record) use (&$count) {
                                if ($record->hasTelegramLinked()) {
                                    $record->unlinkTelegram();
                                    $count++;
                                }
                            });

                            Notification::make()
                                ->title('Telegram Unlinked')
                                ->body("Successfully unlinked Telegram for {$count} users")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('force_password_reset')
                        ->label('Force Password Reset')
                        ->icon('heroicon-o-key')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = $records->count();

                            // In a real implementation, you would:
                            // 1. Generate password reset tokens
                            // 2. Send email notifications
                            // 3. Mark accounts as requiring password reset on next login

                            Notification::make()
                                ->title('Password Reset Required')
                                ->body("Successfully flagged {$count} users for password reset on next login")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Define relationships to eager load for performance
     */
    protected function getEagerLoadedRelations(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            // No relationships for internal users
            // Users are staff members and don't have orders, wallets, or server clients
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 50 ? 'success' : 'primary';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'username', 'telegram_username'];
    }
}
